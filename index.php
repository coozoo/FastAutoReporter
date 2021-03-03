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
    $isdevrun="false";
    $runname="";
    $allchecked="";
    
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
    if(isset($query['isdevrun']))
    {
	$isdevrun=$query['isdevrun'];
    }

    if(isset($query['runname']))
    {
	$runname=$query['runname'];
    }
    if(isset($query['pagename']))
    {
	$pagename=$query['pagename'];
    }
    $teamselection="&nbsp;<b>Team:</b><select id=\"teamselection\">";
    if(isset($query['teamid']))
    {
	$teamid=$query['teamid'];
	$teamselection.="<option value=\"NULL\">All</option>";
	
    }
    else
    {
	
	$teamselection.="<option value=\"NULL\" selected=\"selected\">All</option>";
    }
    $testtypeselection="&nbsp;<b>Test Type:</b><select id=\"testtypeselection\">";
    if(isset($query['testtypeid']) && strtolower($query['testtypeid'])!='null')
    {
	$testtypeid=$query['testtypeid'];
	$testtypeselection.="<option value=\"NULL\">All</option>";
    }
    else
    {
	$allchecked="checked";
	$testtypeselection.="<option value=\"NULL\" selected=\"selected\">All</option>";
    }
    $featureselection="&nbsp;<b>Feature:</b><select id=\"featureselection\">";
    if(isset($query['featureid']))
    {
	$featureid=$query['featureid'];
	$featureselection.="<option value=\"NULL\">All</option>";
    }
    else
    {
	$featureselection.="<option value=\"NULL\" selected=\"selected\">All</option>";
    }
////////environment selector beginning
    $envselection="&nbsp;<b>Environment:</b><select id=\"envselection\">";
    if(isset($query['environment']))
    {
	$environment="'".$query['environment']."'";
	$environmentr=str_replace("'","",$environment);
	if($environment=="'ALL'")
	{
	    $environment="NULL";
	}
	$envselection.="<option value=\"ALL\">All</option>";
    }
    else
    {
	$envselection.="<option value=\"ALL\" selected=\"selected\">All</option>";
    }
    
    $cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
    echo("<!DOCTYPE html><html>
    <head><title>Fast Automation Report Viewer</title>
        <link rel='shortcut icon' type='image/png' href='$iconfile' /> <style id=\"csstablestyle\">".$cssTableStyle."</style>
        </head>
        <body>");
    $headertest="";
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/header.php");
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if(!isset($mysqli))
    {
        echo "Connection failed";
    }
///////////////////////////////////////////////////
///////////////// Prepare Environment Selector
///////////////////////////////////////////////////
    $environmentsquery="SELECT * FROM reporter.environment;";
    //    echo($environmentsquery);
    if($result = $mysqli->query($environmentsquery))
    {
	while($rows=mysqli_fetch_array($result)){
	//    echo($rows[0].$rows[1]);
	    if($rows[1]==$environmentr)
	    {
		$envselection.="<option value=\"".$rows[1]."\" selected=\"selected\">".$rows[1]."</option>";
	    }
	    else
	    {
		$envselection.="<option value=\"".$rows[1]."\">".$rows[1]."</option>";
	    }
	}
	
	$result->close();
	//$mysqli->next_result();
    }
    $envselection.="</select>";
    
//echo $envselection;
//strtoupper
///////////////////////////////////////////////////
///////////////// Prepare isdev checkbox
///////////////////////////////////////////////////
    $idevrunstate="";
    if($isdevrun=="true")
    {
	$idevrunstate="checked";
    }
    $isdevcheckbox="<table><tr><td nowrap><input title=\"Show Dev Runs\" type=\"checkbox\" id=\"isdevcheckbox\" $idevrunstate ><span id=\"isdevspan\" style=\"color:black;font-weight:bold;cursor:pointer;\" onclick=\"return isdevmarked();\">Show Dev Runs</span></td></tr></table>";

//////////////////////////////////////////
///////////// Prepare TestType Selector
//////////////////////////////////////////


$testtypesselectionradio="<div  class=\"tabset\" ><div  class=\"tabset\" id=\"radiotesttypetabset\">";
$testtypesselectionradio.="<input type=\"radio\" class=\"radio\" name=\"testtyperadio\" id=\"radio-ALL\" value=\"NULL\"  $allchecked /><label class=\"tab\" onclick=\"return testtypeRadio('radio-ALL');\"  for=\"ALL\">ALL</label>";

$testtypesquery="SELECT * FROM reporter.testtype;";
    if($result = $mysqli->query($testtypesquery))
    {
	while($rows=mysqli_fetch_array($result)){
	    //echo($rows[0].$rows[1]);
	    if($rows[0]==$testtypeid)
	    {
		$testtypeselection.="<option value=\"".$rows[0]."\" selected=\"selected\">".$rows[1]."</option>";
		$testtypesselectionradio.="<input type=\"radio\" class=\"radio\" name=\"testtyperadio\" id=\""."radio-"."$rows[0]\"  value=\"$rows[0]\" checked /><label  class=\"tab\"  onclick=\"return testtypeRadio('"."radio-"."$rows[0]');\"  for=\"$rows[1]\">$rows[1]</label>";

	    }
	    else
	    {
		$testtypeselection.="<option value=\"".$rows[0]."\">".$rows[1]."</option>";
		$testtypesselectionradio.="<input type=\"radio\" class=\"radio\" name=\"testtyperadio\"  id=\""."radio-"."$rows[0]\"  value=\"$rows[0]\" /><label  class=\"tab\"  onclick=\"return testtypeRadio('"."radio-"."$rows[0]');\"  for=\"$rows[1]\">$rows[1]</label>";

	    }
	}
	
	$result->close();
	//$mysqli->next_result();
    }
    $testtypeselection.="</select>";
    $testtypesselectionradio.="</div><input type=\"image\" id=\"addbookmark\" src=\"img/icons/Gnome-user-bookmarks.svg\" style=\"width:25px;height:25px;margin-bottom:-7px;\" title=\"Add selected filters to bookmark\"></div>";
    
//echo $testtypeselection;



//////////////////////////////////////////
///////////// Prepare Team Selector
//////////////////////////////////////////

$teamsquery="SELECT * FROM reporter.team";
    if($result = $mysqli->query($teamsquery))
    {
	while($rows=mysqli_fetch_array($result)){
	//    echo($rows[0].$rows[1]);
	    if($rows[0]==$teamid)
	    {
		$teamselection.="<option value=\"".$rows[0]."\" selected=\"selected\">".$rows[1]."</option>";
	    }
	    else
	    {
		$teamselection.="<option value=\"".$rows[0]."\">".$rows[1]."</option>";
	    }
	}
	
	$result->close();
	//$mysqli->next_result();
    }
    $teamselection.="</select>";

//echo $teamselection;

//////////////////////////////////////////
///////////// Prepare Feature Selector
//////////////////////////////////////////

/*$featuresquery="SELECT * FROM reporter.feature";
    if($result = $mysqli->query($featuresquery))
    {
	while($rows=mysqli_fetch_array($result)){
	//    echo($rows[0].$rows[1]);
	    if($rows[0]==$featureid)
	    {
		$featureselection.="<option value=\"".$rows[0]."\" selected=\"selected\">".$rows[1]."</option>";
	    }
	    else
	    {
		$featureselection.="<option value=\"".$rows[0]."\">".$rows[1]."</option>";
	    }
	}
	
	$result->close();
	//$mysqli->next_result();
    }
    $featureselection.="</select>";
*/
//echo $featureselection;


////////////////////////////////
//////// Prepare Date Form
////////////////////////////////

#include($_SERVER['DOCUMENT_ROOT']."/$myreporter/datetimeform.php");

$datetime=gmdate("Y-m-d H:i");
$strenddatetime=strtotime($datetime);
$strstartdatetime = strtotime("-7 day", $strenddatetime);
if(!isset($query['startdate']))
{
    $startdate=date('Y-m-d', $strstartdatetime);
}
else
{
    $startdate=$query['startdate'];
}
if(!isset($query['starttime']))
{
    $starttime=date('H:i', $strstartdatetime);
}
else
{
    $starttime=str_replace("_",":",$query['starttime']);
}
$enddatecheckboxinit="";
if(!isset($query['enddate']))
{
    $enddate=date('Y-m-d', $strenddatetime);
}
else
{
    $enddate=$query['enddate'];
    $enddatecheckboxinit="checked";
}
if(!isset($query['endtime']))
{
    $endtime=date('H:i', $strenddatetime);
}
else
{
    $endtime=str_replace("_",":",$query['endtime']);
    $enddatecheckboxinit="checked";
}
$dateselector="<form onsubmit=\"return datesubmitclicked();return false;\" class=\"datetime-form\">";
//$dateselector.="<input type=\"checkbox\" id=\"startdatecheckbox\" onclick=\"return startcheckbox();\" checked>";
//$dateselector.="<table style=\"padding:4px;border: 1px solid\"><tr><td nowrap>";
$dateselector.="<table style=\"padding:4px;border: 4px groove\"><tr><td nowrap>";
$dateselector.="<input type=\"checkbox\" id=\"startdatecheckbox\"  checked>";
$dateselector.="<span id=\"startdatetext\" style=\"color:black;font-weight:bold;cursor:pointer;\" onclick=\"return startspan();\">Start Date:</span><input type=\"date\" id=\"startdate\" value=\"".$startdate."\" max=\"".$enddate."\" required>";
$dateselector.="<input type=\"time\" id=\"starttime\" value=\"".$starttime."\" required>";
$dateselector.="</td><td nowrap>";
$dateselector.="<input type=\"checkbox\" id=\"enddatecheckbox\" $enddatecheckboxinit>";
//$dateselector.="<input type=\"checkbox\" id=\"enddatecheckbox\" onclick=\"return endcheckbox();\">";
$dateselector.="<span id=\"enddatetext\" style=\"color:black;font-weight:bold;cursor:pointer;\" onclick=\"return endspan();\">End Date:</span><input type=\"date\" id=\"enddate\" value=\"".$enddate."\" min=\"".$startdate."\" required>";
$dateselector.="<input type=\"time\" id=\"endtime\" value=\"".$endtime."\" required>";
$dateselector.="</td><td>";
$dateselector.="<input type=\"submit\" id=\"submitdate\" value=\"Set Date\">";
$dateselector.="</td></tr></table>";
$dateselector.="</form>";


//echo $dateselector;

//////////////////////////////////////////
////////// Prepare Item selector
//////////////////////////////////////////
$itemsperpageselection="&nbsp;<b>Items:</b><select id=\"itemsselection\">";
$pagesoptions=array(20,25,30,40,50,60);
$arrlength=count($pagesoptions);

$itemsperpageselection.="<option value=\"NULL\">All</option>";
$insertflag=0;
for($x=0;$x<$arrlength;$x++)
{
    if($items_per_page<$pagesoptions[$x] && $x==0)
    {
	$itemsperpageselection.="<option value=\"$items_per_page\" selected=\"selected\">$items_per_page</option>";
	$insertflag=1;
    }
    elseif($items_per_page<$pagesoptions[$x] && $items_per_page>$pagesoptions[$x-1])
    {
	$itemsperpageselection.="<option value=\"$items_per_page\" selected=\"selected\">$items_per_page</option>";
	$insertflag=1;
    }
    if($pagesoptions[$x]==$items_per_page)
    {
	$itemsperpageselection.="<option value=\"$pagesoptions[$x]\" selected=\"selected\">$pagesoptions[$x]</option>";
	$insertflag=1;
    }
    else
    {
	$itemsperpageselection.="<option value=\"$pagesoptions[$x]\">$pagesoptions[$x]</option>";
    }
}
    if($insertflag==0)
    {
	$itemsperpageselection.="<option value=\"$items_per_page\" selected=\"selected\">$items_per_page</option>";
    }
$itemsperpageselection.="</select>";
//echo($itemsperpageselection);

////////////////////////////////////////////////////
/////////// Prepare runname filter
////////////////////////////////////////////////////

$runnamefilter="";
$runnamefilter.="<table><tr><td nowrap><b>Name:&nbsp;&nbsp;</b><select name=\"filter_condition\" id=\"runnamecondition\"><option value=\"=\">=</option><option value=\"like\">like</option></select>";
$runnamefilter.="<input list=\"runnames_input\" id=\"runnameinput\" name=\"runname\" style=\"width: 13em;\" onkeypress=\"return onkeypress_runname(event);\"><datalist id=\"runnames_input\">";
//maybe here add some query to DB to get list of names better javascript
//$runnamefilter=$runnamefilter."<option value=\"art\">";
$runnamequery="SELECT distinct(r_run_name) FROM reporter.run";
    if($result = $mysqli->query($runnamequery))
    {
	while($rows=mysqli_fetch_array($result)){
		$runnamefilter.="<option value=\"$rows[0]\">";
	}
	
	$result->close();
	//$mysqli->next_result();
    }
$runnamefilter.="</datalist><input type=\"image\" id=\"submitrunname\" src=\"img/icons/Gnome-edit-find.svg\" style=\"width:25px;margin-bottom:-7px;\" title=\"Search for RunName Matches\"></td></tr></table>";

////////////////////////////////////////////////////
/////////// Prepare version filter
////////////////////////////////////////////////////
if(isset($query['equalversion']))
{
	$equalversion=urldecode($query['equalversion']);
}
else
{
    $equalversion="";
}

$versionfilter="";
$versionfilter.="<b>Version:</b><select name=\"versionfilter_condition\" id=\"versioncondition\"><option value=\"=\">=</option><option value=\"like\">like</option></select>";
$versionfilter.="<input list=\"versions_input\" id=\"versioninput\" name=\"version\" style=\"width: 13em;\" onkeypress=\"return onkeypress_version(event);\" value=\"$equalversion\"><datalist id=\"versions_input\">";
//maybe here add some query to DB to get list of versions better javascript
//$versionfilter=$versionfilter."<option value=\"art\">";
$versionfilter.="</datalist><input type=\"image\" id=\"submitversion\" src=\"img/icons/Gnome-edit-find.svg\" style=\"width:25px;margin-bottom:-7px;\" title=\"Search for Version\">";


echo("<div  style=\"position: relative;z-index: 2;top:-29px\"  id=\"table\">
<!--$testypeselectionbutton-->
<!--$testtypesselectionradio-->
$testtypesselectionradio
<table style=\"width:100%;border-spacing:10px;background-color:#c2d9ed;\">
    <tr>
    <td>$envselection</td>
    <td>$isdevcheckbox</td>
    <!--<td><table><tr><td>$testtypeselection</td></tr><tr><td>$teamselection</td></tr></table></td>-->
    <td>$teamselection</td>
    <!--<td>$featureselection</td>-->
    <td style=\"padding:10px 0px;\">$dateselector</td>
    <td><table><tr><td>$runnamefilter</td></tr><tr><td>$versionfilter</td></tr></table></td>
    <td>$itemsperpageselection</td>
</tr></table>
<!--<iframe id=\"frametable\" src=\"\" style=\"height:100%;width:100%\"></iframe>-->
<iframe class=\"curreniframe\" id=\"frametable\" src=\"\" onload=\"this.style.height=this.contentWindow.document.body.scrollHeight + 5 +'px';\" style=\"position:absolute;z-index:10;width:100%\"></iframe>
<!--<iframe class=\"curreniframe\" id=\"frametable1\" src=\"\" onload=\"document.getElementById('frametable').hidden=true;this.style.height=this.contentWindow.document.body.scrollHeight + 5 +'px';\" style=\"position:absolute;z-index:5;width:100%;\"></iframe>-->
</div>");
echo("<script  type=\"text/javascript\">

var selectionl = document.getElementById('envselection');
var itemsselection = document.getElementById('itemsselection');
//var testtypeselection= document.getElementById('testtypeselection');
var teamselection= document.getElementById('teamselection');
var featureselection= document.getElementById('featureselection');
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
var isdevcheckbox=document.getElementById('isdevcheckbox');
var runnamecondition=document.getElementById('runnamecondition');
var versioncondition=document.getElementById('versioncondition');
var runnameinput=document.getElementById('runnameinput');
var versioninput=document.getElementById('versioninput');
var radiotesttypetabset=document.getElementById('radiotesttypetabset');
var addbookmark=document.getElementById('addbookmark');

document.getElementById(\"runnameinput\").value = \"$runname\";

//= featureselection.onchange
//= testtypeselection.onchange 
window.onload = addbookmark.onclick = radiotesttypetabset.onclick = submitversion.onclick = submitrunname.onclick = submitdate.onclick = isdevcheckbox.onchange = startdate.onchange = starttime.onchange = enddate.onchange = endtime.onchange  = teamselection.onchange  = itemsselection.onchange = selectionl.onchange = startdatecheckbox.onchange = enddatecheckbox.onchange = function(){
	console.log(arguments);
	var runnameconditionvalue=runnamecondition.value;
	console.log(runnameconditionvalue);
	var runnameinputvalue=runnameinput.value;
	console.log(runnameinputvalue);
	var versionconditionvalue=versioncondition.value;
	console.log(versionconditionvalue);
	var versioninputvalue=versioninput.value;
	console.log(versioninputvalue);
	
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
    
    selectionValue=selectionl.value;
    itemsselectionValue=itemsselection.value;
    
    //testtypeselectionValue=testtypeselection.value;
    var i;
    var x = document.getElementsByClassName(\"radio\");
    for (i = 0; i < x.length; i++) {
	if(x[i].checked)
	{
	    testtypeselectionValue=x[i].value;
	}
    }
    teamselectionValue=teamselection.value;
    //featureselectionValue=featureselection.value;
    featureselectionValue='NULL';
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
    var isdevreqtext='false';
    if(isdevcheckbox.checked)
    {
	isdevreqtext='true';
    }
    var equalrunname=\"\";
    var likerunname=\"\";
    if(runnameconditionvalue==\"=\")
    {
	equalrunname=runnameinputvalue;
    }
    else
    {
	likerunname=runnameinputvalue;
    }

    var equalversion=\"\";
    var likeversion=\"\";
    if(versionconditionvalue==\"=\")
    {
	equalversion=versioninputvalue;
    }
    else
    {
	likeversion=versioninputvalue;
    }
    
    console.log(isdevreqtext);
    console.log(testtypeselectionValue);
	//document.getElementById(\"environment\").innerHTML=\"<b>\"+selectionValue+\"</b>\";
	//loadDoc(selectionValue);
    //console.log(arguments[0]);
    if(arguments.hasOwnProperty('0') && arguments[0].type=='click')
    {
	var triggerelonclick=arguments[0].target.id;
    }
    if(triggerelonclick!='addbookmark')
    {
	loadTable(selectionValue,itemsselectionValue,testtypeselectionValue,teamselectionValue,featureselectionValue,startdateValue,starttimeValue,enddateValue,endtimeValue,isdevreqtext,equalrunname,likerunname,equalversion,likeversion);
    }
    else
    {
	var url=window.location.origin + window.location.pathname;
	console.log(window.location);
	searchparams='?environment='+selectionValue
	+'&items='+itemsselectionValue
	+'&testtypeid='+testtypeselectionValue
	+'&teamid='+ teamselectionValue
	+'&isdevrun='+isdevreqtext
	+'&equalversion='+equalversion
	+'&runname='+equalrunname;

	url+=searchparams;
	window.location.href = url;
	alert(\"Click OK\\nAnd now you can press Ctrl+D to bookmark the page\");
    }
};

function loadTable(environment,itemsselectionValue,testtypeselectionValue,teamselectionValue,featureselectionValue,startdateValue,starttimeValue,enddateValue,endtimeValue,isdevcheckboxstate,equalrunname,likerunname,equalversion,likeversion) {
    var iframe = document.getElementById(\"frametable\");
    /*var iframe1 = document.getElementById(\"frametable1\");
    var frame;    
    if(iframe.hidden==false)
    {
	frame=iframe1;
    }
    else
    {
	frame=iframe;
    }*/
    
    //var elmnt = iframe.contentWindow.document.getElementsByTagName(\"H1\")[0];
    //elmnt.style.display = \"none\";
    iframe.src = \"runs.php?environment=\"+ environment+ \"&items=\" + itemsselectionValue+\"&testtypeid=\"+ testtypeselectionValue +\"&teamid=\"+ teamselectionValue + \"&featureid=\"+ featureselectionValue
    +\"&startdate=\"+ startdateValue
    +\"&starttime=\"+ starttimeValue
    +\"&enddate=\"+ enddateValue
    +\"&endtime=\"+ endtimeValue 
    +\"&isdevrun=\"+ isdevcheckboxstate 
    +\"&equalversion=\"+ equalversion
    +\"&likeversion=\"+ likeversion
    +\"&likerunname=\"+ likerunname
    +\"&equalrunname=\"+ equalrunname
    +\"&_=\" + new Date().getTime();
    iframe_spinner();
    
}


function onkeypress_runname(event)
{
    if (event.keyCode == 13 || event.which == 13){
        submitrunname.onclick();
    }
}

function onkeypress_version(event)
{
    if (event.keyCode == 13 || event.which == 13){
        submitversion.onclick();
    }
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


function startcheckbox(){
alert('startcheckbox');
}

function endcheckbox(){
alert('endcheckbox');
}

function datesubmitclicked() {
    startdateValue=startdate.value;
    console.log('submit clicked');
    return false;
}

function isdevmarked(){
if(isdevcheckbox.checked)
{
isdevcheckbox.checked=false;
isdevcheckbox.onchange();
}
else
{
isdevcheckbox.checked=true;
isdevcheckbox.onchange();
}
}

function headertest()
{
    console.log('open some link here');
    var iframe=document.getElementById(\"frametable\");
    var datatable=iframe.contentWindow.document.getElementById(\"tablebody\");
    //console.log(iframe);
    //console.log(datatable);
    var headers=datatable.getElementsByTagName(\"th\");
/*    var failid=0,errorid=0,skipid=0,passid=0,totalid=0;
    for (i=0;i<headers.length;i++) {
	//console.log(headers[i]);
	switch(headers[i].innerHTML)
	{
	    case \"FAIL\":
		failid=i;
		break;
	    case \"ERROR\":
		errorid=i;
		break;
	    case \"SKIP\":
		skipid=i;
		break;
	    case \"PASS\":
		passid=i;
		break;
	    case \"Total\":
		totalid=i;
		break;
	}
	
    }
    console.log('ids '+ failid +' ' + errorid + ' ' + skipid + ' ' + passid + ' ' + totalid);
*/
    var json=JSON.parse('{\"data\":[]}');

    var rows=datatable.getElementsByTagName(\"tbody\")[0].getElementsByTagName(\"tr\");
    //console.log(rows);
    for(i=0;i<rows.length;i++)
    {
	var columns=rows[i].getElementsByTagName(\"td\");
	var json_row_obj=JSON.parse('{}');
	for(j=0;j<columns.length;j++)
	{
	    json_row_obj[headers[j].innerHTML]=columns[j].innerHTML;
	}
	json['data'].push(json_row_obj);
	
    }
    console.log(json);
    jsonStr = JSON.stringify(json);
    console.log(jsonStr);
    //var win = window.open('testresultschart.php', '_blank');
    //win.focus();
    var form = document.createElement(\"form\");
    form.setAttribute(\"method\", \"post\");
    form.setAttribute(\"action\", 'testresultschart.php');
    form.setAttribute(\"target\", \"view\");
    var hiddenField = document.createElement(\"input\");
    hiddenField.setAttribute(\"type\", \"hidden\");
    hiddenField.setAttribute(\"name\", \"data\");
    hiddenField.setAttribute(\"value\", jsonStr);
    form.appendChild(hiddenField);
    document.body.appendChild(form);
    window.open('', 'view');
    form.submit();
    document.body.removeChild(form);
}

function iframe_spinner()
{
    var overlay=document.getElementById(\"frametable\").contentWindow.document.getElementById(\"overlay\");
    if (overlay !== null && overlay !==undefined)
    {
	overlay.style.display = \"block\";
    }
    var spinner=document.getElementById(\"frametable\").contentWindow.document.getElementsByClassName('spinner')[0];
    if (spinner !== null && spinner !==undefined)
    {
	spinner.style.display = \"block\";
    }
    //document.getElementById(\"frametable\").contentWindow.document.getElementById(\"overlay\").style.display = \"block\";
    //document.getElementById(\"frametable\").contentWindow.document.getElementsByClassName('spinner')[0].style.display = \"block\";
    //document.getElementById(\"overlay\").style.display = \"none\";
    //document.getElementsByClassName('spinner')[0].style.display = \"none\";
}

function testtypeRadio(testtypeid) {
  var i;
  var x = document.getElementsByClassName(\"radio\");
  for (i = 0; i < x.length; i++) {
    x[i].checked = false;
  }
  document.getElementById(testtypeid).checked = true;
  console.log('testypeid '+ testtypeid);
}


</script>");
echo("</body>
</html>
");

?>
