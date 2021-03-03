<?php

    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php");
    
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    
    $items_per_page=30;
    $items_per_page_param="$items_per_page";
    $page_number=0;
    $pagename=basename(__FILE__);
    $environment="NULL";
    $environmentr=$environment;
    $teamid="NULL";
    $testtypeid="NULL";
    $featureid="NULL";
    $startdate="NULL";
    $enddate="NULL";
    $startdate_param=$startdate;
    $enddate_param=$enddate;
    $starttime="NULL";
    $endtime="NULL";
    $isdevrun="false";
    $equalrunname="NULL";
    $likerunname="NULL";
    $equalversion="''";
    $likeversion="''";
    
    $parts = parse_url($url);
    if(isset($parts['query']))
    {
	parse_str($parts['query'], $query);
    }
    if(isset($query['page']) && is_numeric($query['page']) && $query['page']>=0)
    {
	$page_number=$query['page'];
    }
    
    if(isset($query['items']))
    {
	$items_per_page=$query['items'];
	$items_per_page_param=$query['items'];
    }
    if(isset($query['pagename']))
    {
	$pagename=$query['pagename'];
    }
    if(isset($query['teamid']))
    {
	$teamid=$query['teamid'];
    }
    if(isset($query['testtypeid']))
    {
	$testtypeid=$query['testtypeid'];
    }
    if(isset($query['featureid']))
    {
	$featureid=$query['featureid'];
    }
    //echo $featureid;
    if(isset($query['startdate']))
    {
	$startdate=$query['startdate'];
	$startdate_param=$startdate;
	if(isset($query['starttime']) && $startdate!="NULL")
	{
	    $starttime=$query['starttime'];
	    $startdate=$startdate." ".str_replace("_",":",$starttime);
	}
	if($startdate!="NULL")
	{
	    $startdate="'".$startdate."'";
	}
    }
    //echo $startdate;
    if(isset($query['enddate']))
    {
	$enddate=$query['enddate'];
	$enddate_param=$enddate;
	if(isset($query['endtime']) && $enddate!="NULL")
	{
	    $endtime=$query['endtime'];
	    $enddate=$enddate." ".str_replace("_",":",$endtime);
	}
	if($enddate!="NULL")
	{
	    $enddate="'".$enddate."'";
	}
    }
    //echo $enddate;
    if(isset($query['environment']))
    {
	$environment="'".$query['environment']."'";
	$environmentr=str_replace("'","",$environment);
	if($environment=="'ALL'")
	{
	    $environment="NULL";
	}
    }
    if(isset($query['isdevrun']))
    {
	$isdevrun=$query['isdevrun'];
    }
    if(isset($query['equalrunname']))
    {
	$rqequalrunname=$query['equalrunname'];
	$equalrunname="'".$query['equalrunname']."'";
	if($equalrunname=="''")
	{
	    $equalrunname="NULL";
	}
    }
    if($equalrunname=="NULL")
    {
	$rqequalrunname="";
    }
    if(isset($query['likerunname']))
    {
	$rqlikerunname=$query['likerunname'];
	$likerunname="'".$query['likerunname']."'";
	if($likerunname=="''")
	{
	    $likerunname="NULL";
	}
    }
    if($likerunname=="NULL")
    {
	$rqlikerunname="";
    }
    if(isset($query['equalversion']))
    {
	//if($equalversion==="")
	//{
	    $rqequalversion=$query['equalversion'];
	    $equalversion="'".$query['equalversion']."'";
	//}
	if($query['equalversion']=="N/A")
	{
	    $equalversion="NULL";
	}
    }
    if($equalversion=="''")
    {
	$rqequalversion="";
    }
    //echo $equalversion;

    if(isset($query['likeversion']))
    {
	$rqlikeversion=$query['likeversion'];
	$likeversion="'".$query['likeversion']."'";
	if($likeversion=="'N/A'")
	{
	    $likeversion="NULL";
	}
    }
    if($likeversion=="''")
    {
	$rqlikeversion="";
    }

    function round_up($number, $precision = 0)
    {
	$fig = (int) str_pad('1', $precision, '0');
	return (ceil($number * $fig) / $fig);
    }
    
    $cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);

    echo ("<!DOCTYPE html><html lang=\"en\"><head><title>Test Runs</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
	<script src=\"sorttable.js\" type=\"text/javascript\"></script>
	</head><body>
  <div id=\"overlay\">
  <!--<img src=\"img/anim/spinner.gif\" alt=\"progress\" class=\"image\">-->
</div>
<div class=\"spinner\"></div>
");
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if(!isset($mysqli))
    {
	echo "Connection failed";
    }

    
    //here should be exact procedure with same parameters and conditions to count amount of total available runs
    //this one made only for debugging
    $countquery="CALL count_runs($environment,".$testtypeid.",".$teamid.",".$featureid.",".$startdate.",".$enddate.",$isdevrun,$equalrunname,$likerunname,$equalversion,$likeversion);";
    $testrunscount=0;
//    echo($countquery);
    if($result = $mysqli->query($countquery))
    {
	$row=mysqli_fetch_array($result);
	$testrunscount=$row[0];
	//print "Count:".$testrunscount;
	$result->close();
	$mysqli->next_result();
    }
    else{
	printf("Error: %s\n", $mysqli->error);
    }
    $limitstartrow=$items_per_page*$page_number;
    if($limitstartrow>$testrunscount)
    {
	$page_number=0;
	$limitstartrow=$items_per_page*$page_number;
    }
    //echo("limitstartrow:".$limitstartrow);
    $query="CALL get_runs($environment,".$limitstartrow.",".$items_per_page_param.",".$testtypeid.",".$teamid.",$featureid,$startdate,$enddate,$isdevrun,$equalrunname,$likerunname,$equalversion,$likeversion);";
    //$query=$query."select @test";
    // echo($query);
    //run the store proc
    $table="<table id=\"tablebody\" class=\"blueTable sortable\">";
    $tableheader="<thead><tr>";
    $tablebody="</tr></thead><tbody>";
    $tablefooter="";
    if ($result = $mysqli->query($query))
    {
	$finfo = $result->fetch_fields();
	$trcnt=0;
	$clmncnt=0;
	while($rows=mysqli_fetch_array($result)){
	    //echo "<tr id=\"PASS\">";
	    $tablebody.="<tr>";
	    $RUNID=-1;
	    foreach ($finfo as $val) {
		if($trcnt==0)
		{
		    if($val->name!="RUNID")
		    {
			if (strpos($val->name, '<th>') === false)
			{
			    $tableheader.="<th>".$val->name."</th>";
			}
			else
			{
			    $tableheader.=$val->name;
			}
		    }
		}
		if($val->name=="RUNID")
		{
		    $RUNID=$rows[$val->name];
		    
		}
		else
		{
		    if (strpos($rows[$val->name], '<td>') === false)
		    {
			switch ($val->name)
			{
			case "Name":
			    $tablebody.="<td><b><a href=\"suite.php?runid=".$RUNID."\" target=\"_blank\">".$rows[$val->name]."</a></b></td>";
			    break;
			case "Status":
			    $tablebody.="<td id=\"statuscolid_$RUNID\" value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
			    break;
			case "Version":
			    if(strlen($rows[$val->name])<10)
			    {
				$tablebody.="<td value=\"".$rows[$val->name]."\" onclick=\"return copyversion(this);\"onclick=\"return copyversion(this);\">".$rows[$val->name]."</td>";
			    }
			    else
			    {
				//$tablebody=$tablebody."<td class=\"columnA\" value=\"".$rows[$val->name]."\" title=\"".$rows[$val->name]."\"><a>".substr($rows[$val->name],0,10)."..."."<div class=\"tooltip\">".$rows[$val->name]."</div></a></td>";
				$tablebody.="<td class=\"columnA\" value=\"".$rows[$val->name]."\" title=\"Click to copy into buffer: \n".$rows[$val->name]."\" onclick=\"return copyversion(this);\">".$rows[$val->name]."</td>";
			    }
			    break;
			default:
			    $tablebody.="<td value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
			}
		    }
		    else
		    {
			$tablebody.=$rows[$val->name];
		    }
		}
	    }
	    $tablebody.="</tr>";
	    $trcnt++;
	}
	$clmncnt=substr_count($tableheader,'<th>');
	//echo($clmncnt);
	
	$tablefooter.="<tfoot><tr><td colspan=\"$clmncnt\">";
	$tablefooter.="<div class=\"links\"><a onclick=\"window.parent.parent.scrollTo(0,0);spinner_on();\" href=\"./".$pagename."?environment=".$environmentr."&teamid=".$teamid."&testtypeid=".$testtypeid."&page=0&items=".$items_per_page_param."&startdate=".$startdate_param."&starttime=".$starttime."&enddate=".$enddate_param."&endtime=".$endtime."&isdevrun=".$isdevrun."&equalversion=".$rqequalversion."&likeversion=".$rqlikeversion."&likerunname=".$rqlikerunname."&equalrunname=".$rqequalrunname."\">&laquo;</a>";
	
	$startitem=$page_number*$items_per_page;
	if($items_per_page!=0)
	{
	    $rounded=round_up($testrunscount/$items_per_page);
	}
	else
	{
	    $rounded=1;
	}
	
	if($testrunscount>0 && $page_number>=$rounded)
	{
	    $startitem=($rounded)*$items_per_page;
	    $page_number=($rounded);
	}
	$totalcnt=0;
	$skip=0;
	$prevskip=0;
	$visiblepages=9;
	for ($i = 0; $i < $rounded; $i++) 
	{
	    if($i!=$page_number)
	    {	
		$prevskip=$skip;
		if($i<$visiblepages || ($i>=$page_number-($visiblepages/2) && $i<=$page_number+($visiblepages/2)) || ($i>$page_number-($visiblepages/2) && $i<=$page_number+($visiblepages/2)) || ($i>=$rounded-$visiblepages))
		{
		    $tablefooter.="<a onclick=\"window.parent.parent.scrollTo(0,0);spinner_on();\" class=\"active\" href=\"".$pagename."?environment=".$environmentr."&teamid=".$teamid."&testtypeid=".$testtypeid."&page=".$i."&items=".$items_per_page_param."&startdate=".$startdate_param."&starttime=".$starttime."&enddate=".$enddate_param."&endtime=".$endtime."&isdevrun=".$isdevrun."&equalversion=".$rqequalversion."&likeversion=".$rqlikeversion."&likerunname=".$rqlikerunname."&equalrunname=".$rqequalrunname."\">".($i+1)."</a>";
		    $skip=0;
		}
		else
		{
		    $skip=1;
		}
		if($prevskip!=$skip && $skip==1)
		{
		    $tablefooter.="&nbsp;<font style=\"background-color:powderblue;color:blue;\">...</font>&nbsp;";
		}
		
	    }
	    else
	    {
		$tablefooter.="<a onclick=\"window.parent.parent.scrollTo(0,0);\" style=\"color:blue;font-size:20px;\" class=\"active\" href=\"".$pagename."?environment=".$environmentr."&teamid=".$teamid."&testtypeid=".$testtypeid."&page=".$i."&items=".$items_per_page_param."&startdate=".$startdate_param."&starttime=".$starttime."&enddate=".$enddate_param."&endtime=".$endtime."&isdevrun=".$isdevrun."&equalversion=".$rqequalversion."&likeversion=".$rqlikeversion."&likerunname=".$rqlikerunname."&equalrunname=".$rqequalrunname."\">".($i+1)."</a>";
	    }
	    $totalcnt=$i;
	}
	$tablefooter.="<a onclick=\"window.parent.parent.scrollTo(0,0);spinner_on();\" href=\"".$pagename."?environment=".$environmentr."&teamid=".$teamid."&testtypeid=".$testtypeid."&page=".$totalcnt."&items=".$items_per_page_param."&startdate=".$startdate_param."&starttime=".$starttime."&enddate=".$enddate_param."&endtime=".$endtime."&isdevrun=".$isdevrun."&equalversion=".$rqequalversion."&likeversion=".$rqlikeversion."&likerunname=".$rqlikerunname."&equalrunname=".$rqequalrunname."\">&raquo;</a>";
	$tablefooter.="&nbsp;&nbsp;<input list=\"pagenumber_input\" id=\"pagenumberinput\" name=\"pagenumber\" value=\"".($page_number+1)."\" style=\"width: 2em;text-align: right;\" onkeypress=\"return onkeypress_pagenumber(event);\">";
	$tablefooter.="<input type=\"image\" id=\"submitpage\" src=\"img/icons/Dialog-apply.svg\" style=\"width:25px;margin-bottom:-7px;\" title=\"Go to page\"";
#$versionfilter.="<input list=\"versions_input\" id=\"versioninput\" name=\"version\" style=\"width: 13em;\" onkeypress=\"return onkeypress_version(event);\"><datalist id=\"versions_input\">";
#$versionfilter.="</datalist><input type=\"image\" id=\"submitversion\" src=\"img/icons/Gnome-edit-find.svg\" style=\"width:25px;margin-bottom:-7px;\" title=\"Search for Version\">";
	$tablefooter.="</div></td></tr></tfoot>";

	$result->close();
	$mysqli->next_result();
	if($totalcnt<1)
	{
	    $table.=$tableheader.$tablebody;
	}
	else
	{
	    $table.=$tableheader.$tablefooter.$tablebody;
	}
	if($testrunscount==0)
	{
	    $table.="<b>Nothing found by requested parameters</b>";
	}
	$table.="</tr></tbody></table>";
	echo $table;
    }
echo("<script  type=\"text/javascript\">
var elements = document.querySelectorAll('td[value~=\"InProgress\"]'),i;
if(elements)
{
	for (i = 0; i < elements.length; ++i) {
	    var runid=elements[i].id.split(/[_]+/).pop();
	    console.log(runid);
	    console.log(elements[i].getAttribute(\"value\"));
	    setTimeout(function(){updatestatusfunction(runid,0);},60000);
	 }
}

function updatestatusfunction(elementid,iter)
{
	console.log(elementid + ' ' + iter);
	var targetcol=\"statuscolid_\"+elementid;
	var targetelement=document.getElementById(targetcol);
	loadStatus(elementid);
	console.log(targetelement.getAttribute(\"value\"));
	if(targetelement.getAttribute(\"value\")=='InProgress')
	{
		console.log('trigger');
		setTimeout(function(){updatestatusfunction(elementid,iter+1);},60000);
	}
	else
	{
		console.log('stop');
	}
}



function loadStatus(runid) {
    var targetcol=\"statuscolid_\"+runid;
    var targetelement=document.getElementById(targetcol);
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) 
    {
		    console.log(xhttp.responseText);
		    var response=xhttp.responseText;
		    targetelement.setAttribute(\"value\",response);
		    targetelement.innerHTML=response;
    }
    else if (this.readyState != 4 && this.status != 200)
    {
	console.log('loading:'+runid);
    }
    else if (this.readyState == 4 && this.status != 200)
    {
    	console.log('something wrong:'+runid);
    }
    };
	xhttp.open(\"GET\", \"getrunstatus.php?runid=\"+ runid +\"&_=\" + new Date().getTime(), true);
	xhttp.send();
	
}

function copyversion(el) {
    console.log(el);
    var dummy = document.createElement('input'),
    text=el.innerHTML;
    console.log(text);
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand('copy');
    document.body.removeChild(dummy);
}
function spinner_on() {
  document.getElementById(\"overlay\").style.display = \"block\";
  document.getElementsByClassName('spinner')[0].style.display = \"block\";
}

function spinner_off() {
    var overlay=document.getElementById(\"overlay\");
    if (overlay !== null)
    {
	overlay.style.display = \"none\";
    }
    var spinner=document.getElementsByClassName('spinner')[0];
    if (spinner !== null)
    {
	spinner.style.display = \"none\";
    }
}

function onkeypress_pagenumber(event)
{
    if (event.keyCode == 13 || event.which == 13){
        submitpage.onclick();
    }
}

submitpage.onclick=function()
{
    spinner_on();
    console.log('pressed');
    console.log(document.getElementById(\"pagenumberinput\").value);
    window.open (\"".$pagename."?environment=".$environmentr."&teamid=".$teamid."&testtypeid=".$testtypeid."&page=\"+(document.getElementById(\"pagenumberinput\").value-1)+\"&items=".$items_per_page_param."&startdate=".$startdate_param."&starttime=".$starttime."&enddate=".$enddate_param."&endtime=".$endtime."&isdevrun=".$isdevrun."&equalversion=".$rqequalversion."&likeversion=".$rqlikeversion."&likerunname=".$rqlikerunname."&equalrunname=".$rqequalrunname."\",'_self',false);
}


</script>");
    echo("</body>
    </html>");

    CloseCon($mysqli);
?>
