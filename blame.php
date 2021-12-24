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

    $lastdays=1;
    $who="";
    $statuses="";
    $runid="NULL";
    $teamid="NULL";

    $parts = parse_url($url);
    if(isset($parts['query']))
    {
	parse_str($parts['query'], $query);
    }
    if(isset($query['lastdays']) && is_numeric($query['lastdays']) && $query['lastdays']>=0)
    {
	$lastdays=$query['lastdays'];
    }
    //echo $lastdays;
    if(isset($query['who']))
    {
	$who=str_replace("\"","",$query['who']);
    }
//    echo $who;
    if(isset($query['statuses']))
    {
	$statuses=str_replace("\"","",$query['statuses']);
    }
//    echo $statuses;

    if(isset($query['runid']))
    {
	$runid=$query['runid'];
    }
    if(isset($query['teamid']))
    {
	$teamid=$query['teamid'];
    }
    $cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);

    echo ("<!DOCTYPE html><html lang=\"en\"><head><title>Test Runs</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
	<script src=\"sorttable.js\" type=\"text/javascript\"></script>
	</head><body>");
    $STATUS="blame";
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/header.php"); 
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if(!isset($mysqli))
    {
	echo "Connection failed";
    }

    
    //here should be exact procedure with same parameters and conditions to count amount of total available runs
    //this one made only for debugging
    //$tablequery="call get_blamed(1,'FAIL,ERROR,SKIP','Vladimir Ruzakov');";
    $tablequery="call get_blamed($lastdays,'".$statuses."','".$who."',".$teamid.",".$runid.");";
//    echo($tablequery);

    $introtable="<table id=\"introtablebody\" class=\"blueTable sortable\">";
    $introtableheader="<thead><tr><th>Blaming on</th><th>Count($statuses)</th></tr></thead>";
    $introtablebody="<tbody>";

    $tableinfo="<table id=\"tablebody\" class=\"blueTable sortable\">";
    $tableinfoheader="<thead><tr>";
    $tableinfobody="</tr></thead><tbody>";
    $prevperson="";
    $prevcounter=0;
    $counter=0;
    $RUNID="";
    $TESTID="";
    if ($result = $mysqli->query($tablequery))
    {
	$finfo = $result->fetch_fields();
	$trcnt=0;
	$clmncnt=0;
	$listoftestrailcases="";
	while($rows=mysqli_fetch_array($result)){
	    //echo "<tr id=\"PASS\">";
	    $tableinfobody.="<tr>";
	    foreach ($finfo as $val) {
		if($trcnt==0)
		{
		    if($val->name!="RUNID" && $val->name!="TESTID")
		    {
			$tableinfoheader.="<th>".$val->name."</th>";
		    }
		}
		switch($val->name)
		{
		    case "TestRailID":
			$tableinfobody.="<td value=\"".$rows[$val->name]."\"><a href=\"gettestrailcase.php?caseid=".$rows[$val->name]."\" onclick=\"event.stopPropagation();
										window.open('gettestrailcase.php?caseid=".$rows[$val->name]."','newwindow','status=no,location=no,toolbar=no,menubar=no,resizable=yes,scrollbars=yes,width=1024,height=500,top='+this.getBoundingClientRect().top+',left='+this.getBoundingClientRect().left).focus();return false;\"
										 target=\"_blank\">".$rows[$val->name]."</a></td>";
			if($rows[$val->name]!="")
			{
			    $listoftestrailcases.=$rows[$val->name].",";
			}
			break;
		    case "RUNID":
			$RUNID=$rows[$val->name];
			break;
		    case "TESTID":
			$TESTID=$rows[$val->name];
			break;
		    case "TestName":
			$tableinfobody.="<td value=\"".$rows[$val->name]."\"><a href=\"suite.php?runid=$RUNID#testrow_$TESTID\">".$rows[$val->name]."</a></td>";
			break;
		    default:
			if($prevperson!=$rows[$val->name])
			{
			    $tableinfobody.="<td value=\"".$rows[$val->name]."\"><a name=\"".$rows[$val->name]."\">".$rows[$val->name]."</a></td>";
			}
			else
			{
			    $tableinfobody.="<td value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
			}
		}
//		if($val->name=="RUNID")
//		{
//		    $RUNID=$rows[$val->name];
//		}
//		else
//		{
//		    $tablebody.="<td><b><a href=\"suite.php?runid=".$RUNID."\" target=\"_blank\">".$rows[$val->name]."</a></b></td>";    
//		}
		if($val->name=="Author")
		{
		    if($prevperson==$rows[$val->name])
		    {
			$counter++;
		    }
		    else if($prevperson!="")
		    {
			
			$counter=0;
		    }
		    if($prevperson!=$rows[$val->name] && $prevperson!="")
		    {
			$introtablebody.="<tr><td value=\"".$prevperson."\"><a href=\"#$prevperson\">".$prevperson."</a></td><td><a href='".basename(__FILE__)."?lastdays=".$lastdays."&who=\"".$prevperson."\"&statuses=\"".$statuses."\"&teamid=".$teamid."&runid=".$runid."'>".($prevcounter+1)."</a></td></tr>";
		    }
		    $prevperson=$rows[$val->name];
		    $prevcounter=$counter;
		    
		    
		}
	    }
	    
	    $tableinfobody.="</tr>";
	    $trcnt++;
	}
	if($trcnt>0)
	{
	    $introtablebody.="<tr><td value=\"".$prevperson."\"><a href=\"#$prevperson\">".$prevperson."</a></td><td><a href='".basename(__FILE__)."?lastdays=".$lastdays."&who=\"".$prevperson."\"&statuses=\"".$statuses."\"&teamid=".$teamid."&runid=".$runid."'>".($prevcounter+1)."</a></td></tr>";
	}
	if($listoftestrailcases!="")
	{
	    $listoftestrailcases=rtrim($listoftestrailcases,",");
	}
//	$introtablebody.="<tr><td value=\"".$prevperson."\"><a href=\"#$prevperson\">".$prevperson."</a></td><td><a href='".basename($_SERVER['PHP_SELF'])."?who=\"".$prevperson."\"&statuses=\"".$statuses."\"'>$prevcounter</a></td></tr>";
	$clmncnt=substr_count($tableinfoheader,'<th>');
	//echo($clmncnt);
	
	$result->close();
	//$mysqli->next_result();
        $tableinfo.=$tableinfoheader.$tableinfobody;
	$tableinfo.="</tr></tbody></table>";

	$introtable.=$introtableheader.$introtablebody;
	$introtable.="</tr></tbody></table>";
	
    }
    else
    {
	printf("Error: %s\n", $mysqli->error);
    }


echo "<p><h3>Summary</h3>";
echo $introtable;
echo "</p><p><h4>Details</h4>";
echo ("<button class=\"copybutton\" onclick=\"return copyTRs('hiddendiv');\">Copy TestRail IDs</button>");
echo $tableinfo;
echo("</p>");

echo("<div id=\"hiddendiv\" style=\"display: none;\">$listoftestrailcases</div>");
echo("<script  type=\"text/javascript\">
function copyTRs(containerid)
{
  var textarea = document.createElement('textarea')
  textarea.id = 'temp_element'
  textarea.style.height = 0
  document.body.appendChild(textarea)
  textarea.value = document.getElementById(containerid).innerText
  var selector = document.querySelector('#temp_element')
  selector.select()
  document.execCommand('copy')
  document.body.removeChild(textarea)
}
</script>");

echo("</body></html>");

?>
