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
    $parts=parse_url($url);
    
    $runname="NULL";
    $runnameparam="NULL";
    $testname="NULL";
    $testnameparam="NULL";
    
    $startdate="NULL";
    $enddate="NULL";
    $startdate_param=$startdate;
    $enddate_param=$enddate;
    $starttime="NULL";
    $endtime="NULL";

    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['runname'])) {
	$runnameparam=$query['runname'];
        $runname="'".$query['runname']."'";
    }
    if (isset($query['testname'])) {
	$testnameparam=$query['testname'];
        $testname="'".$query['testname']."'";
    }
    
    if(isset($query['startdate']))
    {
	$startdate=$query['startdate'];
	$startdate_param=$startdate;
	if(isset($query['starttime']) && $startdate!="NULL")
	{
	    $starttime=$query['starttime'];
	    $startdate.=" ".str_replace("_",":",$starttime);
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
	    $enddate.=" ".str_replace("_",":",$endtime);
	}
	if($enddate!="NULL")
	{
	    $enddate="'".$enddate."'";
	}
    }
    //echo $endtdate;


$cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
            echo("<!DOCTYPE html><html lang=\"en\"><head><title>Test History</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
        	    </head><body>");
 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/header.php");
$datetime=gmdate("Y-m-d H:i");
$strenddatetime=strtotime($datetime);
$strstartdatetime = strtotime("-7 day", $strenddatetime);
$startdate=date('Y-m-d', $strstartdatetime);
$starttime=date('H:i', $strstartdatetime);
$enddate=date('Y-m-d', $strenddatetime);
$endtime=date('H:i', $strenddatetime);
$dateselector="<form onsubmit=\"return datesubmitclicked();return false;\" class=\"datetime-form\">";
//$dateselector.="<input type=\"checkbox\" id=\"startdatecheckbox\" onclick=\"return startcheckbox();\" checked>";
//$dateselector.="<table style=\"padding:4px;border: 1px solid\"><tr><td nowrap>";
$dateselector.="<table style=\"padding:4px;border: 4px groove\"><tr><td nowrap>";
$dateselector.="<input type=\"checkbox\" id=\"startdatecheckbox\"  checked>";
$dateselector.="<span id=\"startdatetext\" style=\"color:black;font-weight:bold;cursor:pointer;\" onclick=\"return startspan();\">Start Date:</span><input type=\"date\" id=\"startdate\" value=\"".$startdate."\" max=\"".$enddate."\" required>";
$dateselector.="<input type=\"time\" id=\"starttime\" value=\"".$endtime."\" required>";
$dateselector.="</td><td nowrap>";
$dateselector.="<input type=\"checkbox\" id=\"enddatecheckbox\">";
//$dateselector.="<input type=\"checkbox\" id=\"enddatecheckbox\" onclick=\"return endcheckbox();\">";
$dateselector.="<span id=\"enddatetext\" style=\"color:black;font-weight:bold;cursor:pointer;\" onclick=\"return endspan();\">End Date:</span><input type=\"date\" id=\"enddate\" value=\"".$enddate."\" min=\"".$startdate."\" required>";
$dateselector.="<input type=\"time\" id=\"endtime\" value=\"".$endtime."\" required>";
$dateselector.="</td><td>";
$dateselector.="<input type=\"submit\" id=\"submitdate\" value=\"Set Date\">";
$dateselector.="</td></tr></table>";
$dateselector.="</form>";

echo("<div id=\"table\">
<table style=\"width:100%;border-spacing:10px;background-color:#c2d9ed\">
    <tr>
    <td style=\"padding:10px 0px;\">$dateselector</td>
</tr></table>
<!--<iframe id=\"frametable\" src=\"\" style=\"height:100%;width:100%\"></iframe>-->
<iframe id=\"frametable\" src=\"\" onload=\"this.style.height=this.contentWindow.document.body.scrollHeight + 5 +'px';\" style=\"width:100%\"></iframe>
</div>");

echo("<script  type=\"text/javascript\">
var submitdate= document.getElementById('submitdate');
var startdatetext=document.getElementById('startdatetext');
var enddatetext=document.getElementById('enddatetext');
var startdatecheckbox=document.getElementById('startdatecheckbox');
var enddatecheckbox=document.getElementById('enddatecheckbox');
var laststartdatecheckboxstate=startdatecheckbox.checked;
var lastenddatecheckboxstate=enddatecheckbox.checked;
var startdate=document.getElementById('startdate');
var starttime=document.getElementById('starttime');
var enddate=document.getElementById('enddate');
var endtime=document.getElementById('endtime');

window.onload = submitdate.onclick  = startdate.onchange = starttime.onchange = enddate.onchange = endtime.onchange  = startdatecheckbox.onchange = enddatecheckbox.onchange = function(){
	console.log(arguments);
	
	if(arguments.hasOwnProperty('0') && arguments[0].type=='change')
	{
	    console.log(arguments[0].type);
	    var prevstartdateValue=startdate.value;
	    var prevstarttimeValue=starttime.value;
	    var prevenddateValue=enddate.value;
	    var prevendtimeValue=endtime.value;
	    console.info( arguments[0].target.id );
	    //startdate.setAttribute('max', null);
	    var triggerelement=arguments[0].target.id;
	    if(startdate.value==enddate.value)
	    {
		var startdateD=new Date(startdate.value +' '+ starttime.value);
		var enddateD=new Date(enddate.value +' '+ endtime.value);
		console.log(startdateD);
		console.log(enddateD);
		//if(isNaN(startdateD) || isNaN(enddate))
		if(startdateD.getTime()>=enddateD.getTime())
		{
		    console.log('starttimetime max reached, set starttime to zero');
		    starttime.value='00:00';
		}
		else
		{
		    console.log('set min max time');
		    starttime.setAttribute('max',endtime.value);
		    endtime.setAttribute('min',starttime.value);
		}
	    }
	    else
	    {
		console.log('removetime limit, set date min max');
		starttime.setAttribute('max',null);
		endtime.setAttribute('min',null);
		
		var startdateD=new Date(startdate.value);
		var enddateD=new Date(enddate.value);
		console.log(startdateD);
		console.log(enddateD);
		//if(startdateD.getTime()<enddateD.getTime())
		//{
		//    startdate.value=prevstartdateValue;
		//    enddate.value=prevendValue;
		//}
		//else
		if(!isNaN(startdateD) && !isNaN(enddateD))
		{
		    startdate.setAttribute('max', enddate.value);
		    enddate.setAttribute('min', startdate.value);
		}
		
	    }
	    if(triggerelement=='startdate' || triggerelement=='starttime' || triggerelement=='enddate' || triggerelement=='endtime')
	    {
		console.log('dates triggered');
		return;
	    }
	}
	if(startdatecheckbox.checked)
	{
	    startdatetext.style.color='black';
	    startdate.disabled=false;
	    starttime.disabled=false;
	}
	else
	{
	    startdatetext.style.color='grey';
	    startdate.disabled=true;
	    starttime.disabled=true;
	}

	if(enddatecheckbox.checked)
	{
	    enddatetext.style.color='black';
	    enddate.disabled=false;
	    endtime.disabled=false;
	}
	else
	{
	    enddatetext.style.color='grey';
	    enddate.disabled=true;
	    endtime.disabled=true;
	}
    if(startdatecheckbox.checked==false && enddatecheckbox.checked==false)
    {
	submitdate.disabled=true;
    }
    else
    {
	submitdate.disabled=false;
    }
    if(laststartdatecheckboxstate!=startdatecheckbox.checked)
    {
    
	laststartdatecheckboxstate=startdatecheckbox.checked;
	console.log(laststartdatecheckboxstate);
	if(enddatecheckbox.checked)
	{
	    return;
	}
    }
    
    if(lastenddatecheckboxstate!=enddatecheckbox.checked)
    {

	lastenddatecheckboxstate=enddatecheckbox.checked;
	console.log(lastenddatecheckboxstate);
	if(startdatecheckbox.checked)
	{
	    return;
	}
    }
    
    if(startdatecheckbox.checked)
    {
	startdateValue=startdate.value;
	starttimeValue=starttime.value.replace(':','_');
    }
    else
    {
	startdateValue='NULL';
	starttimeValue='NULL';
    }
    if(enddatecheckbox.checked)
    {
	enddateValue=enddate.value;
	endtimeValue=endtime.value.replace(':','_');
    }
    else
    {
	enddateValue='NULL';
	endtimeValue='NULL';
    }
    var startdateD=new Date(startdate.value +' '+ starttime.value);
    var enddateD=new Date(enddate.value +' '+ endtime.value);

    if(isNaN(startdateD) || isNaN(enddateD) || startdateD>enddateD)
    {
	return;
    }
    console.log('startdate: '+startdateValue);
    console.log('starttime: '+starttimeValue);
    console.log('enddate: '+enddateValue);
    console.log('endtime: '+endtimeValue);


//    console.log(isdevreqtext);
	//document.getElementById(\"environment\").innerHTML=\"<b>\"+selectionValue+\"</b>\";
	//loadDoc(selectionValue);
//	loadTable(selectionValue,itemsselectionValue,teamselectionValue,featureselectionValue,startdateValue,starttimeValue,enddateValue,endtimeValue,isdevreqtext,equalrunname,likerunname,versioninputvalue);
	var environment=\"\";
	//if(arguments.hasOwnProperty('0') && arguments[0].type!='load')
	//{
	    loadtable(startdateValue,starttimeValue,enddateValue,endtimeValue);
	//}
};

function loadtable(startdateValue,starttimeValue,enddateValue,endtimeValue) {
    var iframe = document.getElementById(\"frametable\");
    //var elmnt = iframe.contentWindow.document.getElementsByTagName(\"H1\")[0];
    //elmnt.style.display = \"none\";
    iframe.src =\"testhistory.php?runname=$runnameparam&testname=$testnameparam\"
    +\"&startdate=\"+ startdateValue
    +\"&starttime=\"+ starttimeValue
    +\"&enddate=\"+ enddateValue
    +\"&endtime=\"+ endtimeValue
//    +\"environment=\"+ environment
    +\"&_=\" + new Date().getTime();
}

function startspan(){
if(startdatecheckbox.checked)
{
startdatecheckbox.checked=false;
startdatecheckbox.onchange();
}
else
{
startdatecheckbox.checked=true;
startdatecheckbox.onchange();
}
}

function endspan(){
if(enddatecheckbox.checked)
{
enddatecheckbox.checked=false;
enddatecheckbox.onchange();
}
else
{
enddatecheckbox.checked=true;
enddatecheckbox.onchange();
}
}


function datesubmitclicked() {
    startdateValue=startdate.value;
    console.log('submit clicked');
    return false;
}
</script>");

    echo("</body>
</html>");

?>
