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
    $parts = parse_url($url);
    if(!isset($runid))
    {
	$runid="NULL";
    }
    if(!isset($runuid))
    {
	$runuid="NULL";
    }
    if(!isset($featureview))
    {
	$featureview="false";
    }
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['runid'])) {
        $runid=$query['runid'];
    }
    if (isset($query['runuid'])) {
        $runuid="'".$query['runuid']."'";
    }
    if (isset($query['featureview'])) {
        $featureview=$query['featureview'];
    }
    $FAIL=0;
    $ERROR=0;
    $SKIP=0;
    $PASS=0;
    
    $PROGRESSSTATUS="";
    $RUNRESULT="";
    $RUNNAME="";

$cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);

 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
///////////////////////////////////////////////////
///////////////// get runid by UUID
///////////////////////////////////////////////////
    if($runid=="NULL")
    {
    //$uuid=$query['uuid'];
    //$runidbyuuidquery="SELECT id as RUNID FROM reporter.suite where s_suite_uid='a0a41b56-8233-43b4-b402-3dc8b8c221dc';";
    $runidbyuuidquery="SELECT id as RUNID FROM reporter.run where r_run_uid=".$runuid.";";
    //echo($runidbyuuidquery);
    if($result = $mysqli->query($runidbyuuidquery))
    {
        while($rows=mysqli_fetch_array($result)){
	    //var_dump($rows);
            $runid=$rows[0];
        }
        $result->close();
        //$mysqli->next_result();
    }
    }

//echo $runid."<br>";
//echo("You requested suit with runid $runid. <br>But you will got... God knows what <br>'cause this page is not ready yet");


$suitetable="<table id=\"suitetable\" class=\"greyGridTable\">";
$suitetableheader="<thead><tr>";
$suitetablebody="</tr></thead><tbody>";


$testtable="<table id=\"testtable\" class=\"greyGridTable\">";
$testtableheader="<thead><tr>";
$testtablebody="</tr></thead><tbody>";
if($featureview=="false")
{
    $suitetablefirstcolname="SuiteTable_SuiteID";
    $testtablefirstcolname="TestTable_SuiteID";
}
else
{
    $suitetablefirstcolname="SuiteTable_FeatureID";
    $testtablefirstcolname="TestTable_FeatureID";
}

$runtable="<table id=\"runtable\" class=\"comicGreen\">";

$runtableheader="<thead><tr>";
$runtablebody="</tr></thead><tbody>";
$runtablefirstcolname="RunName";

$runtableresult="<table id=\"runtable\" class=\"comicGreen\">";
$runtableresultheader="<thead><tr>";
$runtableresultbody="</tr></thead><tbody>";

$testtabledata=array();
$suitetabledata=array();

    if($featureview=="false")
    {
	$getsuitquery="call get_suit($runid,$runuid)";
    }
    else
    {
	$getsuitquery="call get_feature($runid,$runuid)";
    }
    //echo $getsuitquery;
    if (!$mysqli->multi_query($getsuitquery)) {
        echo "Multi query failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }
    do {
        $trid=0;
        $currentfirstcolname="";
        //echo $trid."\n";

        if ($result = $mysqli->store_result()) {
            //var_dump($result->fetch_all(MYSQLI_ASSOC));
            //do that for each table row
            while ($rows=mysqli_fetch_array($result)) {
                $finfo = $result->fetch_fields();
                $tcol=0;
                $suiteid=0;
                $suitetablerowarr=array();
                //for each column of table
                foreach ($finfo as $val) {
                
                    //add indicator of first column
                    if ($tcol==0) {
                        $currentfirstcolname=$val->name;
			// add new row by type of table
                if ($currentfirstcolname==$runtablefirstcolname) {
                    $runtablebody.="<tr>";
                }
                elseif($currentfirstcolname==$suitetablefirstcolname)
                {
            	    $suitetablebody.="<tr>";

                }
		elseif($currentfirstcolname==$testtablefirstcolname)
                {

            	    $testtablebody.="<tr>";
                }
                    }
                    //add columns to run table
                    if ($currentfirstcolname==$runtablefirstcolname) {
                        if ($trid==0) {
                    	    if($val->name=="FAIL" || $val->name=="SKIP" || $val->name=="PASS" || $val->name=="ERROR")
                    	    
                    	    {
                        	//$runtableheader.="<th id=\"RunTableHeader_$val->name\" onclick=\"return showhidetests(this);\"  style=\"cursor:pointer;\">".$val->name."</th>";
                        	$runtableresultheader.="<th id=\"RunTableHeader_$val->name\" onclick=\"return showhidetests(this);\"  style=\"cursor:pointer;\" title=\"Show only tests with result $val->name\">".$val->name."</th>";
                    	    }
                    	    else if($val->name=="TOTAL"
                    	    || $val->name=="FAIL%" || $val->name=="SKIP%" || $val->name=="PASS%" || $val->name=="ERROR%")
                    	    {
                    		$runtableresultheader.="<th id=\"RunTableHeader_$val->name\">".$val->name."</th>";
                    	    }
                    	    else
                    	    {
                    		$runtableheader.="<th id=\"RunTableHeader_$val->name\">".$val->name."</th>";
                    	    }
                        }
                    	if($val->name=="Status")
                    	{
                    	    $PROGRESSSTATUS=$rows[$val->name];
                    	}
                        if($val->name=="RunResult")
                        {
                    	    $RUNRESULT=$rows[$val->name];
			    $runtablebody.="<td id=\"".$val->name."\" value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
                        }
			else
			{
			    if($val->name=="FAIL" || $val->name=="SKIP" || $val->name=="PASS" || $val->name=="ERROR"  || $val->name=="TOTAL"
			    || $val->name=="FAIL%" || $val->name=="SKIP%" || $val->name=="PASS%" || $val->name=="ERROR%")
			    {
				$runtableresultbody.="<td id=\"".$val->name."\" value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
			    }
			    else
			    {
				
				if($val->name=="RunName")
				{
				    $runtablebody.="<td id=\"".$val->name."\" value=\"".$rows[$val->name]."\">".$rows[$val->name]."&nbsp;<a href=\"downloadrunlogs.php?runid=$runid\">".
									    "<img src=\"img/icons/Gnome-document-save.svg\" style=\"width:20px; height:20px\" title=\"Download Full Logs\" alt=\"Downlaod\">".
									    "</a></td>";
				}
				else
				{
				    $runtablebody.="<td id=\"".$val->name."\" value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
				}
			    }
			    if($val->name=="RunName")
			    {
				$RUNNAME=$rows[$val->name];
			    }
			}
			
                        if($val->name=="PASS")
                        {
                    	    $PASS=$rows[$val->name];
                        }
                        else if($val->name=="FAIL")
                        {
                    	    $FAIL=$rows[$val->name];
                        }
                        else if($val->name=="ERROR")
                        {
                    	    $ERROR=$rows[$val->name];
                        }
                        else if($val->name=="SKIP")
                        {
                    	    $SKIP=$rows[$val->name];
                        }

                    }
                    // add columns to suite table
                    elseif ($currentfirstcolname==$suitetablefirstcolname) {
                	
                	//echo("build it");
                	if(strpos($val->name,"SuiteTable_")!==false)
                	{
                	    if($val->name=="$suitetablefirstcolname")
                        	{
                        	    $suiteid=$rows[$val->name];
                    		}
                	    //echo("matched name");
                	
                	    if ($trid==0) {
                		if($val->name!="$suitetablefirstcolname")
                		{
                        	    $suitetableheader.="<th>".str_replace("SuiteTable_","",$val->name)."</th>";
                        	    
                        	}
                        	
                    	    }
                    	    if($val->name!="$suitetablefirstcolname")
                		{
                		//echo("make table");
                    		$suitetablebody.="<td>".$rows[$val->name]."</td>";
                    		$suitetabledata[$suiteid][str_replace("SuiteTable_","",$val->name)]=$rows[$val->name];
                    	    }
                    	    
                    	}
                    	                	
                    }
		    elseif($currentfirstcolname==$testtablefirstcolname)
		    {
			if($val->name=="$testtablefirstcolname")
                        	{
                        	    $suiteid=$rows[$val->name];
                    		}
			if ($trid==0) {
                		if($val->name!="$testtablefirstcolname" && $val->name!="TestTable_TestID")
                		{
                        	    $testtableheader.="<th>".str_replace("TestTable_","",$val->name)."</th>";
                        	}
                    	    }
                    	    if($val->name!="$testtablefirstcolname" && $val->name!="TestTable_TestID")
                		{
                		//echo("make table");
                    		$testtablebody.="<td>".$rows[$val->name]."</td>";
                    		$testtabledata[$suiteid][$rows["TestTable_TestID"]][str_replace("TestTable_","",$val->name)]=$rows[$val->name];
                    	    }
                    	    
			
		    }
                    
                    $tcol++;
                }
    
        	if ($currentfirstcolname==$runtablefirstcolname) {
            	    //$runtablebody=$runtablebody."</tr>";
        	}
        	elseif($currentfirstcolname==$suitetablefirstcolname)
        	{
            	    $suitetablebody.="</tr>";    
        	}
		elseif($currentfirstcolname==$testtablefirstcolname)
        	{
            	    $testtablebody.="</tr>";
        	}
                $trid++;
            }
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());


$runtable.=$runtableheader.$runtablebody;
$runtable.="</tr></tbody></table>";

$runtableresult.=$runtableresultheader.$runtableresultbody;
$runtableresult.="</tr></tbody></table>";

$suitetable.=$suitetableheader.$suitetablebody;
$suitetable.="</tr></tbody></table>";

$testtable.=$testtableheader.$testtablebody;
$testtable.="</tr></tbody></table>";

//echo var_dump($testtabledata);
//echo var_dump($suitetabledata);

$totalresulttables="";
$suitekeys = array_keys($suitetabledata);
$suitetrcnt=0;
foreach($suitekeys as $suitekey) {
    //echo($suitekey." ");
    $resultsuitetable="";
    $resultsuitetablename="";
    $resultsuitetableheader="";
    $resultsuitetablename=$resultsuitetablename."<table id=\"suitetable_$suitekey\" class=\"{tablestyle}\" value=\"{suitestatus}\">";
    $resultsuitetableheader=$resultsuitetableheader."<thead id=\"suiteheader_$suitekey\" style=\"cursor:pointer;\" onclick=\"return showhidesuiterow(this);\"><tr>";
    $columnnames = array_keys($suitetabledata[$suitekey]);
    $SuiteStatusColID=-1;
    $SuiteNameColID=-1;
    $suitethcnt=0;
    foreach($columnnames as $columnname) {
	$resultsuitetableheader=$resultsuitetableheader."<td>$columnname</td>";
	if($columnname=="SuiteStatus" || $columnname=="FeatureStatus" )
	{
	    $SuiteStatusColID=$suitethcnt;
	}
	else if($columnname=="SuiteName")
	{
	    $SuiteNameColID=$suitethcnt;
	}
	$suitethcnt++;
    }
    
    $resultsuitetable="</tr></thead><tbody id=\"suitebody_$suitekey\">";
    $resultsuitetablevalues="<tr id=\"suiterow_$suitekey\" style=\"cursor:pointer;\" onclick=\"return showhidesuiterow(this);\">";
    $testtablecolor="";
    $suitetdcnt=0;
    foreach($suitetabledata[$suitekey] as $columnvalue) {
	    if($SuiteNameColID==$suitetdcnt)
	    {
		$resultsuitetablevalues.="<td value=\"$columnvalue\">".
								"<a href=\"#suiterow_$suitekey\" onclick=\"event.stopPropagation();return copyAnchor('suiterow_'+$suitekey);\">".
								"<img src=\"img/icons/Gnome-emblem-symbolic-link.svg\" style=\"width:15px; height:15px\" title=\"Copy Anchor\" alt=\"Copy Anchor \">".
								"</a>&nbsp;$columnvalue</td>";
	    }
	    else
	    {
		$resultsuitetablevalues.="<td value=\"$columnvalue\">$columnvalue</td>";
	    }
	    if($SuiteStatusColID==$suitetdcnt)
	    {
		switch ($columnvalue) {
		    case "PASS":
			$testtablecolor="greenTable";
		        $resultsuitetablename=str_replace("{tablestyle}",$testtablecolor."Outer",$resultsuitetablename);
		        $resultsuitetablename=str_replace("{suitestatus}","PASS",$resultsuitetablename);
		    break;
		    case "FAIL":
			$testtablecolor="redTable";
		        $resultsuitetablename=str_replace("{tablestyle}",$testtablecolor."Outer",$resultsuitetablename);
		        $resultsuitetablename=str_replace("{suitestatus}","FAIL",$resultsuitetablename);
		    break;
		    case "ERROR":
			$testtablecolor="redTable";
			$resultsuitetablename=str_replace("{tablestyle}",$testtablecolor."Outer",$resultsuitetablename);
			$resultsuitetablename=str_replace("{suitestatus}","ERROR",$resultsuitetablename);
		    break;
		    case "SKIP":
			$testtablecolor="greyTable";
			$resultsuitetablename=str_replace("{tablestyle}",$testtablecolor."Outer",$resultsuitetablename);
			$resultsuitetablename=str_replace("{suitestatus}","SKIP",$resultsuitetablename);
		    break;
		    default:
			$testtablecolor="blueTable";
			$resultsuitetablename=str_replace("{tablestyle}",$testtablecolor,$resultsuitetablename);
			$resultsuitetablename=str_replace("{suitestatus}","unknown",$resultsuitetablename);
		}
	    }
	    $suitetdcnt++;
	}
    $resultsuitetablevalues.="</tr><tr id=\"suitetestsrow_$suitekey\"><td id=\"suitetestscolumn_$suitekey\" colspan=\"$suitethcnt\">";

    $testkeys = array_keys($testtabledata[$suitekey]);
    //echo var_dump($testkeys);
    $parttesttabledata=$testtabledata[$suitekey];
//    echo var_dump($parttesttabledata);
    $testtrcnt=0;
    $testthcnt=0;
    $TestStatusColID=-1;
    $TestVideoColID=-1;
    $TestNameColID=-1;
    $TestRailIDColID=-1;
    $TestUUIDColID=-1;
    $TestDefectColID=-1;
    $DefectInvertedCounter=0;
    $ShowDefectColumn=false;
    
    
    foreach($testkeys as $testkey) {
	if($parttesttabledata[$testkey]['Defect']===NULL || $parttesttabledata[$testkey]['Defect']==='')
	{
	    $DefectInvertedCounter++;
	}
    }
    
//    echo $DefectInvertedCounter;
//    echo count($testkeys);
    if($DefectInvertedCounter!=count($testkeys))
    {
	$ShowDefectColumn=true;
    }
    //echo var_dump($ShowDefectColumn);
    foreach($testkeys as $testkey) {
//    echo $testkey;
	$resulttesttable="";
	$resulttesttablename="";
	$resulttesttableheader="";
	
	$testcolumnnames = array_keys($parttesttabledata[$testkey]);
	if($testtrcnt==0)
	{
	$resulttesttablename.="<table id=\"testtable_$suitekey\" class=\"$testtablecolor"."Inner"."\">";
	$resulttesttableheader.="<thead id=\"testheader_$testkey\"><tr>";
	foreach($testcolumnnames as $testcolumnname) {
	    if($testcolumnname!="TestUUID" && $testcolumnname!="Defect"  && $testcolumnname!="TestVideo")
	    {
		$resulttesttableheader.="<th>$testcolumnname</th>";
	    }
	    else if($testcolumnname=="Defect" && $ShowDefectColumn)
	    {
		$resulttesttableheader.="<th>$testcolumnname</th>";
	    }
	    else if($testcolumnname=="TestUUID")
	    {
		$TestUUIDColID=$testthcnt;
	    }
	    else if($testcolumnname=="TestVideo")
	    {
		$TestVideoColID=$testthcnt;
	    }
	    
	    switch($testcolumnname){
		case "TestResult":
		    $TestStatusColID=$testthcnt;
		break;
		case "TestName":
		    $TestNameColID=$testthcnt;
		break;
		case "TestRailID":
		    $TestRailIDColID=$testthcnt;
		break;
		case "TestVideo":
		    $TestVideoColID=$testthcnt;
		break;
		case "Defect":
		    $TestDefectColID=$testthcnt;
		break;
	    }

	    $testthcnt++;
	}
	$resulttesttableheader.="</tr>";
	$resulttesttable="</thead><tbody id=\"testbody_$testkey\">";
	}
	
	$tempresulttesttablevalues="<tr id=\"testrow_$testkey\" value=\"{testresult}\" uuid=\"".$parttesttabledata[$testkey]['TestUUID']."\" style=\"cursor:pointer;\" onclick=\"return showhidetestdetails(this);\">";
	$tempvideovalue="";
	//print_r($parttesttabledata[$testkey]['TestVideo']);
	if(!is_null($parttesttabledata[$testkey]['TestVideo']))
	{
	    $tempvideovalue="</a>&nbsp;".
		    	    "<a href=\"testvideo.php?testid=$testkey\" onclick=\"event.stopPropagation();\" target=\"_blank\">".
		    	    "<img src=\"img/icons/Gnome-video-x-generic.svg\" style=\"width:15px; height:15px\" title=\"Video\" alt=\"View Video \">";
	}

	//print_r($parttesttabledata[$testkey]['TestUUID']);
	$testtdcnt=0;
	$columnsresulttesttablevalues="";
	$resulttesttablevalues="";
	//echo "TestStatusColID:$TestStatusColID";
	//echo $testkey."<br>";
	foreach($parttesttabledata[$testkey] as $testcolumnvalue) {
	    if($TestUUIDColID!=$testtdcnt && $TestVideoColID!=$testtdcnt)
	    {
		if($TestNameColID==$testtdcnt)
		{
		    $columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\">".
									    "<a href=\"#testrow_$testkey\" onclick=\"event.stopPropagation();return copyAnchor('testrow_'+$testkey);\">".
									    "<img src=\"img/icons/Gnome-emblem-symbolic-link.svg\" style=\"width:15px; height:15px\" title=\"Copy Anchor\" alt=\"Copy Anchor \">".
									    "</a>&nbsp;".
									    "<a href=\"testhistoryloader.php?runname=$RUNNAME&testname=$testcolumnvalue\" onclick=\"event.stopPropagation();\" target=\"_blank\">".
									    "<img src=\"img/icons/Gnumeric.svg\" style=\"width:15px; height:15px\" title=\"Test History\" alt=\"Test History \">".
									    "$tempvideovalue".
									    "</a>&nbsp;$testcolumnvalue&nbsp;</td>";
		}
		elseif($TestRailIDColID==$testtdcnt)
		{
			$columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\"><a href=\"gettestrailcase.php?caseid=$testcolumnvalue\" onclick=\"event.stopPropagation();
										window.open('gettestrailcase.php?caseid=$testcolumnvalue','newwindow','status=no,location=no,toolbar=no,menubar=no,resizable=yes,scrollbars=yes,width=1024,height=500,top='+this.getBoundingClientRect().top+',left='+this.getBoundingClientRect().left).focus();return false;\"
										 target=\"_blank\">$testcolumnvalue</a></td>";
		//	$columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\"><font id=\"font_$testcolumnvalue\" style=\"font-weight:bold;text-decoration: underline;\" onclick=\"event.stopPropagation();
		//								return gettestrailcase(this,this.getBoundingClientRect().top);\">$testcolumnvalue</font></td>";
		}
		elseif($TestDefectColID==$testtdcnt && $ShowDefectColumn)
		{
		    $columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\">$testcolumnvalue</td>";
		}
		elseif($TestDefectColID!=$testtdcnt)
		{
		    $columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\">$testcolumnvalue</td>";
		}
		if($TestStatusColID==$testtdcnt)
		{
		    $tempresulttesttablevalues=str_replace("{testresult}",$testcolumnvalue,$tempresulttesttablevalues);
		}
	    }
	    $testtdcnt++;
	}
	$resulttesttablevalues=$tempresulttesttablevalues.$columnsresulttesttablevalues;
	
	$resulttesttablevalues.="</tr><tr id=\"testlogsrow_$testkey\"><td id=\"testscolumn_$testkey\" colspan=\"$testthcnt\">";
	//$resulttesttablevalues.="<iframe id=\"testsframe_$testkey\" src=\"\" onload=\"this.style.height=this.contentWindow.document.body.scrollHeight +'px';\" style=\"width:100%;height:0px;\" hidden></iframe>";
	$resulttesttablevalues.="</td>";
	$resulttesttable.=$resulttesttablevalues;
	$resulttesttable.="</tr>";
	$resultsuitetablevalues.=$resulttesttablename.$resulttesttableheader.$resulttesttable;
	$testtrcnt++;
    }
    $resultsuitetablevalues.="</tbody></table>";
    
    
    
    $resultsuitetablevalues.="</td>";
    $resultsuitetable.=$resultsuitetablevalues;
    $resultsuitetable.="</tr></tbody></table><p id=\"suitetablebr_$suitekey\"><p>";
    $totalresulttables.=$resultsuitetablename.$resultsuitetableheader.$resultsuitetable;
    $suitetrcnt++;
}

if(isset($RUNRESULT))
{
    
    if($PROGRESSSTATUS=='Finished')
    {
	$STATUS=$RUNRESULT;
    }
    else
    {
	$STATUS=$PROGRESSSTATUS;
    }
}

echo("<!DOCTYPE html><html lang=\"en\"><head><title>Fast Automation Report Viewer - Suite</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
        	<script src=\"sorttable.js\" type=\"text/javascript\"></script>
        	    </head><body>");
include($_SERVER['DOCUMENT_ROOT']."/$myreporter/header.php"); 
if($RUNNAME==null)
{
    exit( "Sorry, such run doesn't exist in DB");
}

echo "<table style=\"width:100%;\"><tbody><tr style=\"vertical-align:top\">";
echo "<td>&nbsp;&nbsp;<iframe allowtransparency=\"true\" src=\"statuspiechart.php?PASS=$PASS&FAIL=$FAIL&SKIP=$SKIP&ERROR=$ERROR\" type=\"image/svg+xml\" scrolling=\"no\" style=\"width:290px;height:160px;border-width:0;\"></iframe></td>";
// onload=\"this.style.height=this.contentWindow.document.body.scrollHeight + 500 +'px';\"
echo  "<td><table  style=\"width:100%;\"><tr><td>".$runtable."</td></tr><tr><td>&nbsp;</td></tr>";
echo  "<tr><td style=\"float:right;\">";

if($STATUS!="PASS")
{
    echo "<a href='blame.php?lastdays=1&statuses=\"FAIL,ERROR\"&teamid=NULL&runid=".$runid."'>".
    "<img src=\"img/icons/finger_pointing_at_you.png\" style=\"width:25px; height:20px\" title=\"Punish them\" alt=\"Punish them\"></a>";
}
else
{
    echo "<img src=\"img/icons/finger_pointing_at_you.png\" style=\"width:25px; height:20px\" title=\"This time without victims\" alt=\"This time without victims\">";
}

echo "<table><tr><td>".$runtableresult."</td></tr></table></td></tr></table></td>";
echo "</tr></tbody></table><br>";
if($featureview=="false")
{
    echo "<div style=\"margin-right:auto;display: inline-block;padding-left: 10px;\"><a href=\"feature.php?runid=$runid\"><img alt=\"Feature View\" src=\"img/icons/Gnome-applications-office.svg\" style=\"width:30px;\" title=\"Switch to Feature View\"></a></div>";
}
else
{
    echo "<div style=\"margin-right:auto;display: inline-block;padding-left: 10px;\"><a href=\"suite.php?runid=$runid\"><img alt=\"Feature View\" src=\"img/icons/Gnome-applications-office.svg\" style=\"width:30px;\" title=\"Switch to Feature View\"></a></div>";
}


echo "&nbsp;<div style=\"margin-left:auto;float:right;text-align:right;padding-right: 10px;display: inline-block;\">
<input id=\"showhidesuites_FAIL\" type=\"image\" src=\"img/icons/Gnome-colors-emblem-desktop4.svg\" style=\"width:30px;\" onclick=\"return showhidesuites(this);\" title=\"Hide Fail Suites\">
<input id=\"showhidesuites_ERROR\" type=\"image\" src=\"img/icons/Gnome-colors-emblem-desktop2.svg\" style=\"width:30px;\" onclick=\"return showhidesuites(this);\" title=\"Hide Error Suites\">
<input id=\"showhidesuites_SKIP\" type=\"image\" src=\"img/icons/Gnome-colors-emblem-desktop5.svg\" style=\"width:30px;\" onclick=\"return showhidesuites(this);\" title=\"Hide Skip Suites\">
<input id=\"showhidesuites_PASS\" type=\"image\" src=\"img/icons/Gnome-colors-emblem-desktop3.svg\" style=\"width:30px;\" onclick=\"return showhidesuites(this);\" title=\"Hide Pass Suites\">
&nbsp;&nbsp;<input id=\"showhideallsuites\" type=\"image\" src=\"img/icons/Gnome-view-sort-descending.svg\" style=\"width:30px;\" onclick=\"return showHideAllSuites();\" title=\"Expand Suites\">
</div>&nbsp;";

echo("<div style=\"border-right: 5px solid lavender;margin-left:auto;float:right;text-align:right;padding-right: 10px;display: inline-block; background-color: #cecccc;padding:5px;-moz-border-radius:10px 10px 0 0;   \">
<input type=\"checkbox\" style=\"vertical-align: middle;\" id=\"hide_info_checkbox\" title=\"Hide [INFO] logs\"  onclick=\"return onshowhidecheckbox_checked(this);\" checked><label  title=\"Hide [INFO] logs\" style=\"vertical-align: middle;\" for=\"hide_info_checkbox\">Hide [INFO]</label>
<input type=\"checkbox\" style=\"vertical-align: middle;\" id=\"hide_other_checkbox\" title=\"Hide Other except Error/Fail logs\"  onclick=\"return onshowhidecheckbox_checked(this);\"><label  title=\"Hide Other except Error/Fail logs\" style=\"vertical-align: middle;\" for=\"hide_other_checkbox\">Hide Other</label>
</div>&nbsp;&nbsp;");

echo $totalresulttables;

//echo("<br><br>");

//echo $suitetable;

//echo("<br><br>");

//echo $testtable;

echo("<script  type=\"text/javascript\">

var currentUrl = document.URL,
urlParts = currentUrl.split('#');
var anchorelementid='';
if(urlParts.length > 1)
{
    anchorelementid=urlParts[1];
}

var uuidelement=document.querySelector(\"tr[uuid='\"+ anchorelementid +\"']\");
//console.log(uuidelement.id);
if(uuidelement!== null)
{
    anchorelementid=uuidelement.id;
    uuidelement.scrollIntoView();
    //console.log(urlParts[0]+'&_='+ (new Date().getTime())+'#'+uuidelement.id);
    //window.location.href=urlParts[0]+'#'+uuidelement.id;
    //window.location.href=urlParts[0]+'&_='+ (new Date().getTime())+'#'+uuidelement.id;
    //window.location.reload(true);
}

var elements = document.querySelectorAll('thead[id^=\"suiteheader_\"]'),i;
for (i = 0; i < elements.length; ++i) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(document.getElementById('suitetestsrow_'+elements[i].id.split(/[_]+/).pop()).outerHTML,'text/html');
    if(doc.getElementById(anchorelementid)!== null)
    {
	console.log('do not hide');
	if(uuidelement!== null)
	{
	    uuidelement.scrollIntoView();
	}
    }
    else
    {
	showhidesuiterow(elements[i]);
    }
  }
  
//console.log(elements);
if(anchorelementid!='')
{
var anchorelement=document.getElementById(anchorelementid);
var oricellcolor=anchorelement.style.backgroundColor;
setTimeout(function(){blinkfunction(anchorelementid,oricellcolor,0);},0);
if(anchorelementid.includes(\"testrow_\"))
{
    console.log('this is testtow');
    showhidetestdetails(anchorelement);
}
}

function showhidesuiterow(el) {
    //console.log(el);
    var firedid = el.id.split(/[_]+/).pop();
    //console.log(firedid);
    var targetel=\"suitetestsrow_\"+firedid;
    if (document.getElementById(targetel).style.display === \"none\") {
        console.log(\"visible\");
        document.getElementById(targetel).style.display = \"\";
    } else {
	//console.log(\"hidden\");
	document.getElementById(targetel).style.display = \"none\";
    }
}

function showhidetestdetails(el) {
    console.log(el);
    var firedid = el.id.split(/[_]+/).pop();
    var statusvalue = el.getAttribute('value');
    console.log(firedid);
    console.log(statusvalue);
    var targetel=\"testlogsrow_\"+firedid;
    var targetcol=\"testscolumn_\"+firedid;
    console.log(document.getElementById(targetcol).innerHTML.length);
    if(!document.getElementById(targetcol).innerHTML.length)
        {
    	    //document.getElementById(targetcol).innerHTML=getdetails(firedid);
	    getdetails(firedid,statusvalue);
    	    //loadDoc(firedid,statusvalue);
	    console.log(\"request test data\");
        }
        else
        {
    if (document.getElementById(targetel).style.display === \"none\") {
        console.log(\"visible\");
        document.getElementById(targetel).style.display = \"\";
        document.getElementById('frametable_'+firedid).style.height=document.getElementById('frametable_'+firedid).contentWindow.document.body.scrollHeight+40 +'px';
        
    } else {
	console.log(\"hidden\");
	document.getElementById(targetel).style.display = \"none\";
    }
    }
}

function showHideAllSuites()
{
    var  buttonshowhide=document.getElementById('showhideallsuites');
    var descimg='Gnome-view-sort-descending.svg';
    var ascimg='Gnome-view-sort-ascending.svg';
    for (i = 0; i < elements.length; ++i) {
	var firedid = elements[i].id.split(/[_]+/).pop();
	var targetel=\"suitetestsrow_\"+firedid;
	if(buttonshowhide.title.includes('Expand'))
	{
	    document.getElementById(targetel).style.display = \"\";
	}
	else if(buttonshowhide.title.includes('Collapse'))
	{
	    document.getElementById(targetel).style.display = \"none\";

	}
    }
    if(buttonshowhide.title.includes('Expand'))
    {
	buttonshowhide.src='img/icons/'+ascimg;
	buttonshowhide.title='Collapse Suites';
    }
    else if(buttonshowhide.title.includes('Collapse'))
    {
	buttonshowhide.src='img/icons/'+descimg;
	buttonshowhide.title='Expand Suites';
    }
}

function showhidesuites(el)
{
console.log(el);
var firedstatus = el.id.split(/[_]+/).pop();
console.log(firedstatus);
var color='blue';
if(firedstatus==\"ERROR\")
{
    var elements = document.querySelectorAll('table[value~=\"ERROR\"]'),i;
}
else if(firedstatus==\"FAIL\")
{
    var elements = document.querySelectorAll('table[value~=\"FAIL\"]'),i;
}
else if(firedstatus==\"SKIP\")
{
    var elements = document.querySelectorAll('table[value~=\"SKIP\"]'),i;
}
else if(firedstatus==\"PASS\")
{
    var elements = document.querySelectorAll('table[value~=\"PASS\"]'),i;
}
if(elements)
{
    if(el.style.backgroundColor!=color)
    {
	el.style.backgroundColor = color;
	for (i = 0; i < elements.length; ++i) {
	    var brid=elements[i].id.split(/[_]+/).pop();
	    console.log(brid);
	    console.log(elements[i].getAttribute(\"value\"));
	    elements[i].style.display = \"none\";
	    document.getElementById(\"suitetablebr_\"+brid).display = \"none\";
	 }
    }
    else
    {
	el.style.backgroundColor = '';
	for (i = 0; i < elements.length; ++i) {
	var brid=elements[i].id.split(/[_]+/).pop();
	elements[i].style.display = \"\";
	document.getElementById(\"suitetablebr_\"+brid).display = \"\";
	}
    }
    
}
}

function showhidetests(el)
{
//console.log(el);

var firedstatus = el.id.split(/[_]+/).pop();
console.log(firedstatus);
var color='blue';
if(el.style.backgroundColor!=color)
    {
	el.style.backgroundColor = color;
    }
    else
    {
	el.style.backgroundColor = '';
    }
var elFAIL=document.getElementById('RunTableHeader_FAIL');
var elERROR=document.getElementById('RunTableHeader_ERROR');
var elSKIP=document.getElementById('RunTableHeader_SKIP');
var elPASS=document.getElementById('RunTableHeader_PASS');
var initselectorfilter=['tr[value~=\"FAIL\"]','tr[value~=\"ERROR\"]','tr[value~=\"SKIP\"]','tr[value~=\"PASS\"]'];
var selectorfilter=initselectorfilter;
/*if(elFAIL.style.backgroundColor==color)
{
    var index = selectorfilter.indexOf('tr[value~=\"FAIL\"]');
    if (index !== -1) selectorfilter.splice(index, 1)
}
if(elERROR.style.backgroundColor==color)
{
    var index = selectorfilter.indexOf('tr[value~=\"ERROR\"]');
    if (index !== -1) selectorfilter.splice(index, 1)
}
if(elSKIP.style.backgroundColor==color)
{
    var index = selectorfilter.indexOf('tr[value~=\"SKIP\"]');
    if (index !== -1) selectorfilter.splice(index, 1)
}
if(elPASS.style.backgroundColor==color)
{
    var index = selectorfilter.indexOf('tr[value~=\"PASS\"]');
    if (index !== -1) selectorfilter.splice(index, 1)
}*/
if(selectorfilter==0)
{
    return;
}
else
{
var filter=selectorfilter.join(',');
var elements = document.querySelectorAll(filter);
}
//console.log(filter);
/*if(firedstatus==\"ERROR\")
{
    var elements = document.querySelectorAll('tr[value~=\"FAIL\"],tr[value~=\"SKIP\"],tr[value~=\"PASS\"]'),i;
}
else if(firedstatus==\"FAIL\")
{
    var elements = document.querySelectorAll('tr[value~=\"ERROR\"],tr[value~=\"SKIP\"],tr[value~=\"PASS\"]'),i;
}
else if(firedstatus==\"SKIP\")
{
    var elements = document.querySelectorAll('tr[value~=\"FAIL\"],tr[value~=\"ERROR\"],tr[value~=\"PASS\"]'),i;
}
else if(firedstatus==\"PASS\")
{
    var elements = document.querySelectorAll('tr[value~=\"FAIL\"],tr[value~=\"SKIP\"],tr[value~=\"ERROR\"]'),i;
}*/

if(elements)
{
    if(elFAIL.style.backgroundColor==color || elERROR.style.backgroundColor==color || elSKIP.style.backgroundColor==color || elPASS.style.backgroundColor==color)
    {
    for (i = 0; i < elements.length; ++i) {
	    var brid=elements[i].id.split(/[_]+/).pop();
//	    console.log(brid);
	    switch(elements[i].getAttribute(\"value\"))
	    {
	    case 'FAIL':
		if(elFAIL.style.backgroundColor==color)
		{
		    elements[i].style.display = \"\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"\";
		}
		else
		{
		    elements[i].style.display = \"none\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"none\";
		}
		break;
	    case 'ERROR':
		if(elERROR.style.backgroundColor==color)
		{
		    elements[i].style.display = \"\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"\";
		}
		else
		{
		    elements[i].style.display = \"none\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"none\";
		}
		break;
	    case 'SKIP':
		if(elSKIP.style.backgroundColor==color)
		{
		    elements[i].style.display = \"\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"\";
		}
		else
		{
		    elements[i].style.display = \"none\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"none\";
		}
		break;
	    case 'PASS':
		if(elPASS.style.backgroundColor==color)
		{
		    elements[i].style.display = \"\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"\";
		}
		else
		{
		    elements[i].style.display = \"none\";
		    document.getElementById(\"testlogsrow_\"+brid).style.display = \"none\";
		}
		break;
		
	    }
	 }
    }
    else
    {
	for (i = 0; i < elements.length; ++i) {
	    var brid=elements[i].id.split(/[_]+/).pop();
	    elements[i].style.display = \"\";
	    document.getElementById(\"testlogsrow_\"+brid).style.display = \"\";
	}
    }
}
hidesuitesifempty();
}

function hidesuitesifempty()
{
    var testtableelements = document.querySelectorAll('table[id^=\"testtable_\"]'),i;
    for (i = 0; i < testtableelements.length; ++i) {
	var suiteid = testtableelements[i].id.split(/[_]+/).pop();
//	console.log(suiteid);
	var parser = new DOMParser();
	var doctable = parser.parseFromString(document.getElementById('testtable_'+suiteid).outerHTML,'text/html');
//	console.log(doctable);
	var testrowelements = doctable.querySelectorAll('tr[id^=\"testrow_\"]');
	var containsrow=false;
	for (irow = 0; irow < testrowelements.length; ++irow) {
	    if(testrowelements[irow].style.display == '')
	    {
		containsrow=true;
		break;
	    }
	}
	if(!containsrow)
	{
	    document.getElementById('suitetable_'+suiteid).style.display = \"none\";
	}
	else
	{
	    document.getElementById('suitetable_'+suiteid).style.display = \"\";
	}
    }
}

function getdetails(testid,status)
{
    var targetcol=\"testscolumn_\"+testid;
    var frametable='frametable_'+testid;
    document.getElementById(targetcol).innerHTML='<iframe id=\"'+frametable+'\"  src=\"\" onload=\"showhidelogs('+testid+');this.style.height=this.contentWindow.document.body.scrollHeight+40 +\'px\';\"  style=\"width:100%\"></iframe>';
    //this.contentWindow.document.body.getElementById('spinner').style.display='none';
    var iframe = document.getElementById(frametable);
    iframe.src = 'testdetails.php?testid='+ testid+'&status='+status+'&_=' + new Date().getTime();
//onload=\"this.style.height=this.contentWindow.document.body.scrollHeight +'px';\" style=\"width:100%\"
    
}

function loadDoc(testid,status) {
    var targetcol=\"testscolumn_\"+testid;
    //document.getElementById(targetcol).innerHTML=\"Wait, loading...\";
    var xhttp = new XMLHttpRequest();
    //xhttp.onprogress = updateProgress;
    xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) 
    {
		    var parser = new DOMParser();
		    var doc = parser.parseFromString(xhttp.responseText, \"text/html\");
                //var timestr=doc.getElementById(\"unixtime\").textContent;
       // var unixtime=timeConverter(timestr);
        //doc.getElementById(\"unixtime\").innerHTML = \"<b>Endpoint: \" + environment + \"; Lastupdate: \" + unixtime + \"</b>\";
      //document.getElementById(\"info\").innerHTML =doc.documentElement.outerHTML;
		    //var csstablestyle=doc.getElementById(\"csstablestyle\").outerHTML;
		    
		    //var tfoot=doc.getElementsByTagName(\"tfoot\")[0];
		    //document.getElementById(\"footer\").innerHTML=tfoot.outerHTML;
		    //document.table.removeChild(tfoot);
		    //document.getElementById(\"table\").innerHTML=doc.documentElement.outerHTML;
		    //document.getElementById(\"table\").innerHTML=csstablestyle+table;
		    
		    var table=doc.getElementById(\"logtable\").outerHTML;
		    document.getElementById(targetcol).innerHTML=table;
		    
		    //document.getElementById(targetcol).innerHTML=\"<iframe>\"+doc+\"</iframe>\";
		    
    }
    else if (this.readyState != 4 && this.status != 200)
    {
    	//document.getElementById(targetcol).innerHTML=\"Error: \"+this.status +\" \" + this.readyState;
    	document.getElementById(targetcol).innerHTML=\"<font style='font-weight:bold;color:#5c95f7;'>Wait, loading...</font>\";
    }
    else if (this.readyState == 4 && this.status != 200)
    {
    	//document.getElementById(targetcol).innerHTML=\"Error: \"+this.status +\" \" + this.readyState;
    	document.getElementById(targetcol).innerHTML=\"<font style='font-weight:bold;color:#f77b5c;'>Oops, something wrong...</font>\";
    }
    };
	xhttp.open(\"GET\", \"testdetails.php?testid=\"+ testid +\"&status=\"+status+\"&_=\" + new Date().getTime(), true);
	xhttp.send();
	
}

function copyAnchor(anchor) {
    console.log(anchor);
    var currentUrl = document.URL,
    urlParts = currentUrl.split('#');
    console.log((urlParts.length > 1) ? urlParts[1] : null);
    var dummy = document.createElement('input'),
    //text = window.location.href;
    text=urlParts[0].concat('#',anchor);
    console.log(text);
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand('copy');
    document.body.removeChild(dummy);
}

function blinkfunction(elementid,oricellcolor,iter)
{
	console.log(elementid + ' ' +  oricellcolor + ' ' + iter);
	if(iter<10)
	{
	    if(iter%2)
	    {
		document.getElementById(elementid).style.backgroundColor = \"#C7C3FA\";
		console.log('highlight');
		setTimeout(function(){blinkfunction(elementid,oricellcolor,iter+1);},500);
	    }
	else
	    {
		console.log('restore');
		document.getElementById(elementid).style.backgroundColor = oricellcolor;
		setTimeout(function(){blinkfunction(elementid,oricellcolor,iter+1);},500);
	    }
	}
	else
	{
		console.log('stop');
		document.getElementById(elementid).style.backgroundColor = oricellcolor;
	}
}

var executionstate = document.getElementById('Status');
console.log(executionstate.getAttribute(\"value\"));
if(executionstate.getAttribute(\"value\")=='InProgress')
{
	setTimeout(function(){updatestatusfunction('$runid',0);},60000);
}

function updatestatusfunction(elementid,iter)
{
	console.log(elementid + ' ' + iter);
	var targetcol=elementid;
	var targetelement=document.getElementById('Status');
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
    var targetcol=runid;
    var targetelement=document.getElementById('Status');
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) 
    {
		    console.log(xhttp.responseText);
		    var response=xhttp.responseText;
		    targetelement.setAttribute(\"value\",response);
		    if(response=='Finished')
		    {
				window.location.reload(true);
		    }
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

function gettestrailcase(el,toppos)
{
    console.log(el.innerHTML);
    var elid=el.innerHTML;
    var popup = document.createElement('div');
    popup.className = 'popup';
    popup.style.top=toppos+'px';
    popup.id = elid;
    var cancel = document.createElement('div');
    cancel.className = 'cancel';
    cancel.innerHTML = 'Close';
    cancel.onclick = function (e) { popup.parentNode.removeChild(popup) };
    var message = document.createElement('span');
    message.innerHTML = '<iframe id=\"framecase_'+elid+ '\" src=\"gettestrailcase.php?caseid='+ elid+ '&_=' + new Date().getTime()+'\" onload=\"this.style.height=this.contentWindow.document.body.scrollHeight+40 +\'px\';\"  style=\"width:100%\"></iframe>';
//    var iframe = document.getElementById('framecase_'+elid);
//    iframe.src = 'gettestrailcase.php?caseid='+ elid+'&_=' + new Date().getTime();
    popup.appendChild(message);
    popup.appendChild(cancel);
    
    document.body.appendChild(popup);
}

function onkeypress_body(event)
{
    if (event.keyCode == 13 || event.which == 13){
	console.log(\"Enterpressed\");
	//document.scrollLeft;
	//var elements = document.querySelectorAll('iframe[id^=\"framecase_\"]'),i;
	var elements = document.querySelectorAll('iframe'),i;
	for (i = 0; i < elements.length; ++i) {
	    elements[i].contentWindow.scrollTo(0,0);
    	    console.log(elements[i]);
	}
    }
}

function onshowhidecheckbox_checked(event)
{
	console.log(\"Checked\");
	console.log(\"event\");
	//document.scrollLeft;
	//var elements = document.querySelectorAll('iframe[id^=\"framecase_\"]'),i;
	var elements = document.querySelectorAll('iframe'),i;
	for (i = 0; i < elements.length; ++i) {
    	    el=elements[i].id.split(/[_]+/).pop();
	    if(el!='')
	    {
		showhidelogs(el);
	    }
	}
}




function showhidelogs(testid)
{
    var infochecked = document.getElementById(\"hide_info_checkbox\").checked;
    var otherchecked = document.getElementById(\"hide_other_checkbox\").checked;
    var infofilter='';
    if(infochecked)
    {
	infofilter = \"[INFO]\";
    }

    //var frametable='frametable_71880';
    var frametable='frametable_'+testid;
    console.log(\"showhidelogs \" + frametable);
    var iframe = document.getElementById(frametable);
    var innerDoc = iframe.contentDocument || iframe.contentWindow.document;
    var table = innerDoc.getElementById(\"logtable\");
    if(table)
    {
    var tr = table.getElementsByTagName(\"tr\");
    //console.log(tr);
    for (var i = 0; i < tr.length; i++) 
    {
//	console.log(tr[i].textContent);
        if(infofilter!='' && tr[i].textContent.indexOf(infofilter) > -1)
	{
            tr[i].style.display = \"none\";
        }
	else if(otherchecked && tr[i].textContent.indexOf('[ERROR]') == -1 && tr[i].textContent.indexOf('[FAIL]') == -1 && tr[i].textContent.indexOf('[INFO]') == -1)
	{
            tr[i].style.display = \"none\";
	}
	else {
            tr[i].style.display = \"\";
        }
    }
	iframe.style.height=iframe.contentWindow.document.body.scrollHeight+40 +'px';
    }
}


</script>");


echo("</body>
</html>");
            
CloseCon($mysqli);
?>
