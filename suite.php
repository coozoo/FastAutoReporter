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
	$getsuitquery="call get_suit_v2($runid,$runuid)";
    }
    else
    {
	$getsuitquery="call get_feature_v2($runid,$runuid)";
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
	else if($columnname=="SuiteName" || $columnname=="FeatureName")
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
    $TestXrayIDColID=-1;
    $TestJiraIDColID=-1;
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
	    if($testcolumnname!="TestUUID" && $testcolumnname!="Defect"  && $testcolumnname!="TestVideo"  && $testcolumnname!="TestRailID"  && $testcolumnname!="XrayID"  && $testcolumnname!="JiraID")
	    {
		$resulttesttableheader.="<th>$testcolumnname</th>";
	    }
	    else if($testcolumnname=="TestRailID" && $testrailEnabled)
	    {
		$resulttesttableheader.="<th>$testcolumnname</th>";
	    }
	    else if($testcolumnname=="XrayID" && $xrayEnabled)
	    {
		$resulttesttableheader.="<th>$testcolumnname</th>";
	    }
	    else if($testcolumnname=="JiraID" && $jiraEnabled)
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
		case "XrayID":
		    $TestXrayIDColID=$testthcnt;
		break;
		case "JiraID":
		    $TestJiraIDColID=$testthcnt;
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
			$copilotButton = "";
                    if (isset($copilotEnabled) && $copilotEnabled == true) {
                        $copilotButton = "</a>&nbsp;".
										 "<a href=\"javascript:void(0);\" onclick=\"event.stopPropagation(); analyzeLogsWithUserKey($testkey);\">".
                                         "<img src=\"img/icons/Gnome-face-monkey.svg\" style=\"width:15px; height:15px\" title=\"Analyze with AI\" alt=\"Analyze with AI \"></a>&nbsp;";
                    }
		    $columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\">".
									    "<a href=\"#testrow_$testkey\" onclick=\"event.stopPropagation();return copyAnchor('testrow_'+$testkey);\">".
									    "<img src=\"img/icons/Gnome-emblem-symbolic-link.svg\" style=\"width:15px; height:15px\" title=\"Copy Anchor\" alt=\"Copy Anchor \">".
									    "</a>&nbsp;".
									    "<a href=\"testhistoryloader.php?runname=$RUNNAME&testname=$testcolumnvalue\" onclick=\"event.stopPropagation();\" target=\"_blank\">".
									    "<img src=\"img/icons/Gnumeric.svg\" style=\"width:15px; height:15px\" title=\"Test History\" alt=\"Test History \">".
										"$copilotButton".
									    "$tempvideovalue".
									    "</a>&nbsp;$testcolumnvalue&nbsp;</td>";
		}
		elseif($TestRailIDColID==$testtdcnt && $testrailEnabled)
		{
			$columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\"><a href=\"gettestrailcase.php?caseid=$testcolumnvalue\" onclick=\"event.stopPropagation();
										window.open('gettestrailcase.php?caseid=$testcolumnvalue','newwindow','status=no,location=no,toolbar=no,menubar=no,resizable=yes,scrollbars=yes,width=1024,height=500,top='+this.getBoundingClientRect().top+',left='+this.getBoundingClientRect().left).focus();return false;\"
										 target=\"_blank\">$testcolumnvalue</a></td>";
		//	$columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\"><font id=\"font_$testcolumnvalue\" style=\"font-weight:bold;text-decoration: underline;\" onclick=\"event.stopPropagation();
		//								return gettestrailcase(this,this.getBoundingClientRect().top);\">$testcolumnvalue</font></td>";
		}
		elseif($TestXrayIDColID==$testtdcnt && $xrayEnabled)
		{
			$columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\"><a href=\"getxraycase.php?caseid=$testcolumnvalue\" onclick=\"event.stopPropagation();
										window.open('getxraycase.php?caseid=$testcolumnvalue','newwindow','status=no,location=no,toolbar=no,menubar=no,resizable=yes,scrollbars=yes,width=1024,height=500,top='+this.getBoundingClientRect().top+',left='+this.getBoundingClientRect().left).focus();return false;\"
										 target=\"_blank\">$testcolumnvalue</a></td>";
		}
		elseif($TestJiraIDColID==$testtdcnt && $jiraEnabled)
		{
		    $columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\">$testcolumnvalue</td>";
		}
		elseif($TestDefectColID==$testtdcnt && $ShowDefectColumn)
		{
		    $columnsresulttesttablevalues.="<td value=\"$testcolumnvalue\">$testcolumnvalue</td>";
		}
		elseif($TestDefectColID!=$testtdcnt && $TestRailIDColID!=$testtdcnt && $TestXrayIDColID!=$testtdcnt && $TestJiraIDColID!=$testtdcnt)
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

if($STATUS!="PASS" || $STATUS!="SKIP" )
{
    echo "<a href='blame.php?lastdays=1&statuses=\"FAIL,ERROR\"&teamid=NULL&runid=".$runid."'>".
    "<img src=\"img/icons/finger_pointing_at_you.png\" style=\"width:25px; height:20px;\" title=\"Punish them\" alt=\"Punish them\"></a>";
}
else
{
    echo "<img src=\"img/icons/finger_pointing_at_you.png\" style=\"width:25px; height:20px; opacity:30%\" title=\"This time without victims\" alt=\"This time without victims\">";
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

$repo_options = "";
$repo_prompts_js = "<script>var repoPrompts = {};\n";

if (isset($ai_git_projects) && is_array($ai_git_projects)) {
    foreach ($ai_git_projects as $displayName => $projectData) {
        $repo = $projectData['repo'];
        $prompt = $projectData['prompt'];
        $repo_options .= "<option value=\"" . $repo . "\">" . $displayName . "</option>";
        $repo_prompts_js .= "repoPrompts['" . $repo . "'] = " . json_encode($prompt) . ";\n";
    }
}
$repo_prompts_js .= "</script>\n";

echo("<div style=\"border-right: 5px solid lavender;margin-left:auto;float:right;text-align:right;padding-right: 10px;display: inline-block; background-color: #cecccc;padding:5px;-moz-border-radius:10px 10px 0 0;   \">
<label style=\"vertical-align: middle; font-weight: bold; margin-right: 5px; color: #444;\" for=\"ai_repo_selector\">AI Repo:</label>
<select id=\"ai_repo_selector\" onchange=\"localStorage.setItem('selected_ai_repo', this.value);\" style=\"vertical-align: middle; margin-right: 15px; padding: 2px; border-radius: 4px;\">" . $repo_options . "</select>
<input type=\"checkbox\" style=\"vertical-align: middle;\" id=\"hide_info_checkbox\" title=\"Hide [INFO] logs\"  onclick=\"return onshowhidecheckbox_checked(this);\" checked><label  title=\"Hide [INFO] logs\" style=\"vertical-align: middle;\" for=\"hide_info_checkbox\">Hide [INFO]</label>
<input type=\"checkbox\" style=\"vertical-align: middle;\" id=\"hide_other_checkbox\" title=\"Hide Other except Error/Fail logs\"  onclick=\"return onshowhidecheckbox_checked(this);\"><label  title=\"Hide Other except Error/Fail logs\" style=\"vertical-align: middle;\" for=\"hide_other_checkbox\">Hide Other</label>
</div>&nbsp;&nbsp;");

echo($repo_prompts_js);

echo $totalresulttables;

//echo("<br><br>");

//echo $suitetable;

//echo("<br><br>");

//echo $testtable;

echo("<script  type=\"text/javascript\">

var savedRepo = localStorage.getItem('selected_ai_repo');
var repoSelectorLoad = document.getElementById('ai_repo_selector');
if (savedRepo && repoSelectorLoad) {
    repoSelectorLoad.value = savedRepo;
}

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


// =======================================================================
// AI LOG ANALYZER (Auto-switches between Free & Enterprise based on token start chars)
// =======================================================================

function fetchJiraContextMarkdown(ticketId) {
    return fetch('getxraycasepage.php?caseid=' + encodeURIComponent(ticketId) + '&output=json')
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (!data || data.error) return null;
            let md = \"### JIRA TICKET: \" + data.jira.key + \" (\" + data.jira.type + \")\\n\";
            md += \"**Summary:** \" + data.jira.summary + \"\\n\";
            
            // --- START: Parse Atlassian Document Format (ADF) Description ---
            if (data.jira.description) {
                md += \"**Description:**\\n\";
                if (typeof data.jira.description === 'string') {
                    md += data.jira.description + \"\\n\\n\";
                } else if (typeof data.jira.description === 'object') {
                    // Recursive parser for ADF JSON
                    function parseAdf(node) {
                        if (!node) return \"\";
                        if (typeof node === 'string') return node;
                        let res = \"\";
                        if (node.type === 'text') {
                            res += node.text;
                        } else if (node.type === 'paragraph' && node.content) {
                            res += node.content.map(parseAdf).join(\"\") + \"\\n\\n\";
                        } else if (node.type === 'heading' && node.content) {
                            let lvl = (node.attrs && node.attrs.level) ? node.attrs.level : 1;
                            res += \"#\".repeat(lvl) + \" \" + node.content.map(parseAdf).join(\"\") + \"\\n\\n\";
                        } else if (node.type === 'bulletList' && node.content) {
                            res += node.content.map(li => \"- \" + parseAdf(li)).join(\"\") + \"\\n\";
                        } else if (node.type === 'orderedList' && node.content) {
                            res += node.content.map((li, i) => (i+1) + \". \" + parseAdf(li)).join(\"\") + \"\\n\";
                        } else if (node.type === 'listItem' && node.content) {
                            res += node.content.map(parseAdf).join(\"\").trim() + \"\\n\";
                        } else if (node.content) {
                            res += node.content.map(parseAdf).join(\"\");
                        }
                        return res;
                    }
                    md += parseAdf(data.jira.description) + \"\\n\";
                }
            }
            // --- END: Parse Atlassian Document Format ---

            if (data.xray && data.xray.steps && data.xray.steps.length > 0) {
                md += \"**Xray Steps:**\\n\";
                data.xray.steps.forEach(s => {
                    md += \"Step \" + s.step + \": \" + s.action + \"\\n\";
                    if (s.data) md += \" - Data: \" + s.data + \"\\n\";
                    if (s.result) md += \" - Expected: \" + s.result + \"\\n\";
                });
            }
            return { markdown: md, links: data.jira.links || [] };
        }).catch(e => null);
}

function analyzeLogsWithUserKey(testid) {

    var FREE_MAX_TOTAL_CHARS = $copilot_free_max_total_chars;
    var FREE_MAX_LINE_CHARS = $copilot_free_max_line_chars;
    var FREE_MODEL = '$copilot_free_model';

    var PAID_MAX_TOTAL_CHARS = $copilot_paid_max_total_chars;
    var PAID_MAX_LINE_CHARS = $copilot_paid_max_line_chars;
    var PAID_MODEL = '$copilot_paid_model';

    var firedid = testid; 
    var targetel = \"testlogsrow_\" + firedid;
    var targetcol = \"testscolumn_\" + firedid;
    var targetColElement = document.getElementById(targetcol);
    
    if(!targetColElement || !targetColElement.innerHTML.length) {
        alert(\"Please expand the test row first to load the logs before using AI analysis.\");
        return;
    }

    var apiKey = localStorage.getItem('user_llm_api_key');
    if (!apiKey) {
        apiKey = prompt('Paste GitHub PAT (Free limits) OR Copilot IDE Token starting with ghu_ (Enterprise Limits):');
        if (!apiKey) return;
        localStorage.setItem('user_llm_api_key', apiKey);
    }

    // Auto-detect mode based on the token string!
    var isEnterprise = (apiKey.startsWith('ghu_') || apiKey.startsWith('gho_'));
    
    // Apply dynamic limits based on mode
    var MAX_TOTAL_CHARS = isEnterprise ? PAID_MAX_TOTAL_CHARS : FREE_MAX_TOTAL_CHARS;
    var MAX_LINE_CHARS = isEnterprise ? PAID_MAX_LINE_CHARS : FREE_MAX_LINE_CHARS;
    var selectedModel = isEnterprise ? PAID_MODEL : FREE_MODEL;

    var testRow = document.getElementById('testrow_' + firedid);
    var testMethodName = 'Unknown Test';
    var xrayId = 'Unknown Xray';
    var testStatus = 'Unknown';

    if (testRow) {
        if (testRow.cells[0]) testMethodName = testRow.cells[0].getAttribute('value') || testMethodName;
        if (testRow.cells[1]) xrayId = testRow.cells[1].getAttribute('value') || xrayId;
        if (testRow.cells[3]) testStatus = testRow.cells[3].getAttribute('value') || testStatus;
    }

    var aiContainerId = 'ai_container_' + firedid;
    var aiContainer = document.getElementById(aiContainerId);
    if (!aiContainer) {
        aiContainer = document.createElement('div');
        aiContainer.id = aiContainerId;
        targetColElement.insertBefore(aiContainer, targetColElement.firstChild);
    }

    // Update UI styling based on mode
    var modeText = isEnterprise ? \"🚀 [ENTERPRISE]\" : \"🤖 [FREE TIER]\";
    var modeColor = isEnterprise ? \"#8a2be2\" : \"#5c95f7\";
    var modeBg = isEnterprise ? \"#fdf5ff\" : \"#f9f9fc\";

    aiContainer.innerHTML = \"<div style='padding: 10px; font-weight: bold; color: \" + modeColor + \";'>\" + modeText + \" Initializing AI Analysis for \" + testMethodName + \"...</div>\";
	

    // --- START OF EXACT STACK TRACE PARSER & ARRAY SEARCH ---
    var logNode = targetColElement;
    var frames = targetColElement.querySelectorAll('iframe');
    if (frames.length > 0) {
        try { logNode = frames[0].contentDocument.body; } catch (e) { }
    }
    var logText = logNode.innerText || logNode.textContent || '';
    
    var fileNameToSearch = ''; 
    var classMatch = logText.match(/at com\\.qa\\.[^(]+\\(([^:]+\\.java):\\d+\\)/);
    if (classMatch && classMatch[1]) {
        fileNameToSearch = classMatch[1];
    } else {
        var fallbackMatch = logText.match(/([A-Z][a-zA-Z0-9_]+\\.java):\\d+/);
        if (fallbackMatch && fallbackMatch[1]) {
            fileNameToSearch = fallbackMatch[1];
        }
    }
    
      var repoSelector = document.getElementById('ai_repo_selector');
    var repoName = repoSelector.options[repoSelector.selectedIndex].value;
    
    // Build array of unique search terms
    var searchTerms = [testMethodName];
    
    // Strip .java so GitHub searches for the Class name inside the file!
    var cleanClassName = fileNameToSearch ? fileNameToSearch.replace('.java', '') : '';
    
    if (cleanClassName && cleanClassName !== testMethodName) {
        searchTerms.push(cleanClassName);
    }
    
    console.log('🎯 Searching GitHub for terms: ', searchTerms);

 var fetchPromises = searchTerms.map(function(term) {
        var cleanQuery = term.trim();
        var cleanRepo = repoName.trim();
        console.log('🚀 Sending to proxy -> query: ' + cleanQuery + ' | repo: ' + cleanRepo);
        
        return fetch('fetch_github_code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: cleanQuery, repo: cleanRepo, limit: 3 })
        }).then(res => {
            console.log('📡 Proxy HTTP Status for ' + cleanQuery + ': ' + res.status + ' ' + res.statusText);
            return res.text();
        }).then(text => {
            console.log('📦 Proxy Raw Response Length for ' + cleanQuery + ': ' + text.length);
            if (text.length < 200) console.log('📦 Proxy Raw Body: ' + text);
            return text;
        }).catch(e => {
            console.error('🔥 Fetch Exception for ' + cleanQuery + ':', e);
            return 'Fetch Exception: ' + e.message;
        });
    });

    // Resolve all promises concurrently
    var searchPromise = Promise.all(fetchPromises).then(function(results) {
        var combined = '';
        var seenCodes = {}; 
        
        for (var i = 0; i < results.length; i++) {
            var code = results[i];
            console.log('--- 🔎 Checking proxy result for: ' + searchTerms[i] + ' ---');
            
            if (code && typeof code === 'string' && code.indexOf('Code not found in repository') === -1 && code.trim() !== '' && code.indexOf('Fetch Exception') === -1) {
                if (!seenCodes[code]) {
                    seenCodes[code] = true;
                    combined += '\\n--- ACTUAL REPOSITORY SOURCE CODE (' + searchTerms[i] + ') ---\\n' + code + '\\n';
                    console.log('✅ KEPT code for: ' + searchTerms[i]);
                } else {
                    console.log('⚠️ IGNORED duplicate code for: ' + searchTerms[i]);
                }
            } else {
                console.log('❌ THREW AWAY result for: ' + searchTerms[i] + ' (Was empty, 404, or not found)');
            }
        }
        return combined;
    });


    searchPromise.then(sourceCode => {
        var filePath = sourceCode ? \"Code securely loaded from backend.\" : \"Unknown (could not find in repo)\";

        aiContainer.innerHTML = \"<div style='padding: 10px; font-weight: bold; color: \" + modeColor + \";'>\" + modeText + \" Fetching Context & Parsing logs (Limit: \" + MAX_TOTAL_CHARS + \" chars)...</div>\";

        var jiraContextPromise = Promise.resolve(\"No Jira context available.\");
        if (xrayId && xrayId !== 'Unknown Xray') {
            jiraContextPromise = fetchJiraContextMarkdown(xrayId).then(mainTicket => {
                if (!mainTicket) return \"Failed to load Jira context for \" + xrayId;
                let contextText = mainTicket.markdown + \"\\n\";
                if (isEnterprise && mainTicket.links.length > 0) {
                    aiContainer.innerHTML = \"<div style='padding: 10px; font-weight: bold; color: \" + modeColor + \";'>\" + modeText + \" Enterprise: Fetching \" + mainTicket.links.length + \" linked Jira issues...</div>\";
                    let linkPromises = mainTicket.links.map(link => fetchJiraContextMarkdown(link));
                    return Promise.all(linkPromises).then(linkedTickets => {
                        contextText += \"\\n--- LINKED ISSUES CONTEXT ---\\n\";
                        linkedTickets.forEach(lt => { if (lt) contextText += lt.markdown + \"\\n\"; });
                        return contextText;
                    });
                }
                return contextText;
            });
        }

        return jiraContextPromise.then(finalJiraContext => {

            var logDomNode = targetColElement;
            var iframes = targetColElement.querySelectorAll('iframe');
            if (iframes.length > 0) {
                try { logDomNode = iframes[0].contentDocument.body; } catch (e) { console.log(\"Failed to read iframe:\", e); }
            }

            var allRows = logDomNode.querySelectorAll('tr');
            var allLines = [];
            var importantLines = [];

            for (var i = 0; i < allRows.length; i++) {
                var tr = allRows[i];
                var codeNode = tr.querySelector('td code');
                
                if (codeNode) {
                    var rawLine = codeNode.textContent.trim();
                    if (rawLine) {
                        var cutLine = rawLine.length > MAX_LINE_CHARS ? rawLine.substring(0, MAX_LINE_CHARS) + '...[cut]' : rawLine;
                        allLines.push(cutLine);
                        if (codeNode.querySelector('font')) importantLines.push(cutLine);
                    }
                }
            }

            if (allLines.length === 0) throw new Error('Logs are empty (No <td><code> elements found).');

                       var injectedContext = \"Test Method: \" + testMethodName + \"\\n\" +
                                  \"Repository File Path: \" + filePath + \"\\n\" +
                                  \"Current Status: \" + testStatus + \"\\n\\n\" +
                                  \"--- JIRA / XRAY CONTEXT ---\\n\" +
                                  finalJiraContext + \"\\n\\n\";

            if (sourceCode && sourceCode.trim() !== '') {
                injectedContext += '--- ACTUAL REPOSITORY SOURCE CODE ---\\n' + sourceCode + '\\n\\n';
                console.log('✅ Java Source Code successfully appended to prompt! Length: ' + sourceCode.length);
            } else {
                console.error('❌ NO SOURCE CODE WAS APPENDED! fetch_github_code.php returned: ' + sourceCode);
            }

            var availChars = MAX_TOTAL_CHARS - injectedContext.length;
            if (availChars < 2000) availChars = 2000;

            var fullText = allLines.join('\\n');
            var truncatedLogs = fullText;

            if (fullText.length > availChars) {
                var statusUpper = testStatus.toUpperCase();
                if (statusUpper === 'ERROR' || statusUpper === 'FAIL' || statusUpper === 'SKIP') {
                    var importantText = importantLines.join('\\n');
                    if (importantText.length > availChars) importantText = importantText.slice(-availChars);

                    var remainingBudget = Math.max(0, availChars - importantText.length);
                    var startBudget = Math.floor(remainingBudget / 3);
                    var endBudget = remainingBudget - startBudget;

                    var topLines = [];
                    var topChars = 0;
                    var topIndex = 0;

                    while (topIndex < allLines.length && topChars + allLines[topIndex].length < startBudget) {
                        topLines.push(allLines[topIndex]);
                        topChars += allLines[topIndex].length + 1;
                        topIndex++;
                    }

                    var bottomLines = [];
                    var bottomChars = 0;
                    var bottomIndex = allLines.length - 1;

                    while (bottomIndex >= topIndex && bottomChars + allLines[bottomIndex].length < endBudget) {
                        bottomLines.unshift(allLines[bottomIndex]);
                        bottomChars += allLines[bottomIndex].length + 1;
                        bottomIndex--;
                    }
                    
                    truncatedLogs = \"--- START OF LOGS ---\\n\" + topLines.join('\\n') + \"\\n\\n\" +
                                    \"--- IMPORTANT STYLED LOGS (ERRORS/WARNINGS) ---\\n```text\\n\" + importantText + \"\\n```\\n\\n\" +
                                    \"--- END OF LOGS (CRASH & STACK TRACE) ---\\n\" + bottomLines.join('\\n');
                    
                } else {
                    truncatedLogs = '...[TRUNCATED]...\\n' + fullText.slice(-availChars);
                }
            }
            
            injectedContext += \"LOGS:\\n\" + truncatedLogs;

            var systemPrompt = (typeof repoPrompts !== 'undefined' && repoPrompts[repoName]) ? repoPrompts[repoName] : `$copilot_system_prompt`;
			console.log(systemPrompt);
				
				
            // DYNAMIC ROUTING BASED ON MODE
            var fetchPromise;

            /*if (isEnterprise) {
                console.log(\"=== ENTERPRISE DATA SENT ===\");
                console.log(\"TOTAL PAYLOAD LENGTH:\", injectedContext.length);
				 console.log('=== FULL PAYLOAD SENT TO AI ===\\n' + injectedContext);
                fetchPromise = fetch('copilot_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        apiKey: apiKey,
                        model: selectedModel,
                        messages: [
                            { role: 'system', content: systemPrompt },
                            { role: 'user', content: injectedContext }
                        ]
                    })
                });
            } else {*/
			            if (isEnterprise) {
                console.log(\"=== ENTERPRISE DATA SENT ===\");
                console.log(\"TOTAL PAYLOAD LENGTH:\", injectedContext.length);

                var blobLinks = [];
                var logRows = logDomNode.querySelectorAll('tr');
                for (var r = 0; r < logRows.length; r++) {
                    if (logRows[r].textContent.indexOf('[FAIL]') > -1 || logRows[r].textContent.indexOf('[ERROR]') > -1) {
                        var link = logRows[r].querySelector('a[href*=\"getblob.php\"]');
                        if (link && blobLinks.indexOf(link.href) === -1) blobLinks.push(link.href);
                    }
                }
                
                console.log(\"🔍 Found \" + blobLinks.length + \" attachment link(s) in failing rows.\");

                fetchPromise = Promise.all(blobLinks.slice(0, 2).map(url => fetch(url).then(r => r.blob()).catch(e => null)))
                .then(blobs => {
                    var payload = [];
                    var extraText = '';
                    var imageCount = 0;

                    return Promise.all(blobs.filter(b => b).map(blob => new Promise(res => {
                        var reader = new FileReader();
                        reader.onloadend = () => {
                            if (blob.type.indexOf('image') !== -1) {
                                payload.push({ type: 'image_url', image_url: { url: reader.result } });
                                imageCount++;
                            } else {
                                extraText += '\\n\\n--- ATTACHED FILE (' + blob.type + ') ---\\n' + reader.result;
                            }
                            res();
                        };
                        blob.type.indexOf('image') !== -1 ? reader.readAsDataURL(blob) : reader.readAsText(blob);
                    }))).then(() => {
                        injectedContext += extraText;
                        
                        var finalPayload;
                        if (imageCount > 0) {
                            console.log('📸 Successfully encoded ' + imageCount + ' image(s) for Gemini Vision!');
                            finalPayload = [{ type: 'text', text: injectedContext }].concat(payload);
                        } else {
                            finalPayload = injectedContext;
                        }

                        console.log('=== FINAL PAYLOAD GOING TO PROXY ===', finalPayload);

                        return fetch('copilot_proxy.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                apiKey: apiKey,
                                model: selectedModel,
                                messages: [
                                    { role: 'system', content: systemPrompt },
                                    { role: 'user', content: finalPayload }
                                ]
                            })
                        });
                    });
                });
            } else {
                console.log(\"=== FREE TIER DATA SENT ===\");
                console.log(\"TOTAL PAYLOAD LENGTH:\", injectedContext.length);
                fetchPromise = fetch('https://models.inference.ai.azure.com/chat/completions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + apiKey
                    },
                    body: JSON.stringify({
                        model: selectedModel,
                        messages: [
                            { role: 'system', content: systemPrompt },
                            { role: 'user', content: injectedContext }
                        ],
                        temperature: 0.1
                    })
                });
            }

            return fetchPromise;
        }); // End of jiraContextPromise
    })
    .then(aiResponse => {
        if (!aiResponse.ok) {
            if (aiResponse.status === 413 && !isEnterprise) {
                throw new Error('API Error 413: Content Too Large. The logs exceed the max size allowed by the free API tier.');
            }
            if (aiResponse.status === 401 || aiResponse.status === 403) {
                localStorage.removeItem('user_llm_api_key');
                throw new Error(isEnterprise ? 'Enterprise Token rejected. Make sure your Vim token is still valid!' : 'GitHub Token rejected. Please enter a valid PAT.');
            }
            throw new Error('API Error: ' + aiResponse.statusText);
        }
        return aiResponse.json();
    })
    .then(aiData => {
        var analysisText = aiData.choices[0].message.content;
        
        var formattedHtml = analysisText.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        formattedHtml = formattedHtml.replace(/```[a-zA-Z]*\\n([\\s\\S]*?)```/gi, '<div style=\"background:#2b2b2b; color:#f8f8f2; padding:12px; border-radius:6px; font-family:monospace; white-space:pre-wrap; margin:10px 0; overflow-x:auto;\">\$1</div>');
        formattedHtml = formattedHtml.replace(/`([^`]+)`/g, '<span style=\"background:#e0e0e0; color:#c7254e; padding:2px 5px; border-radius:3px; font-family:monospace; font-size:12px;\">\$1</span>');
        formattedHtml = formattedHtml.replace(/\\*\\*(.*?)\\*\\*/g, '<strong>\$1</strong>');
        
        formattedHtml = formattedHtml.replace(/^###### (.*\$)/gim, '<h6 style=\"margin-top:15px; margin-bottom:5px; color:#222;\">\$1</h6>');
        formattedHtml = formattedHtml.replace(/^##### (.*\$)/gim, '<h5 style=\"margin-top:15px; margin-bottom:5px; color:#222;\">\$1</h5>');
        formattedHtml = formattedHtml.replace(/^#### (.*\$)/gim, '<h4 style=\"margin-top:15px; margin-bottom:5px; color:#222;\">\$1</h4>');
        formattedHtml = formattedHtml.replace(/^### (.*\$)/gim, '<h3 style=\"margin-top:15px; margin-bottom:5px; color:#222;\">\$1</h3>');
        formattedHtml = formattedHtml.replace(/^## (.*\$)/gim, '<h2 style=\"margin-top:15px; margin-bottom:5px; color:#222;\">\$1</h2>');
        formattedHtml = formattedHtml.replace(/^# (.*\$)/gim, '<h1 style=\"margin-top:15px; margin-bottom:5px; color:#222;\">\$1</h1>');
        
        formattedHtml = formattedHtml.replace(/^[-*] (.*\$)/gim, '<li style=\"margin-left:20px; margin-bottom:3px;\">\$1</li>');
        
        var safeMarkdown = encodeURIComponent(analysisText).replace(/'/g, \"\\%27\");

        aiContainer.innerHTML = \"<div style='background: \" + modeBg + \"; padding: 20px; border-radius: 8px; border: 1px solid #d1d5da; margin: 15px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>\" +
            \"<h4 style='margin-top:0; margin-bottom:15px; border-bottom:1px solid #e1e4e8; padding-bottom:10px; color:#24292e; font-size:16px;'>\" + modeText + \" AI Root Cause Analysis</h4>\" +
            \"<div style='white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #24292e;'>\" + formattedHtml + \"</div>\" +
            \"<div style='margin-top: 20px; display: flex; gap: 10px;'>\" +
                \"<button style='padding: 6px 12px; cursor: pointer; border: 1px solid #d1d5da; background: #fafbfc; border-radius: 6px; font-weight:bold; color:#24292e;' onclick='fallbackCopyTextToClipboard(\\\"\" + safeMarkdown + \"\\\")'>📋 Copy Markdown</button>\" +
                \"<button style='padding: 6px 12px; cursor: pointer; border: 1px solid #d1d5da; background: #fafbfc; border-radius: 6px; font-weight:bold; color:#cb2431;' onclick='localStorage.removeItem(\\\"user_llm_api_key\\\"); alert(\\\"Token cleared!\\\");'>Clear Saved AI Token</button>\" +
            \"</div>\" +
        \"</div>\";
    })
    .catch(error => {
        if (aiContainer) {
            aiContainer.innerHTML = \"<div style='color: #cb2431; background-color: #ffeef0; padding: 15px; border: 1px solid #f97583; border-radius: 6px; margin: 15px 0;'><b>Error:</b> \" + error.message + \"</div>\";
        }
    });
}


function fallbackCopyTextToClipboard(encodedText) {
    var rawText = decodeURIComponent(encodedText);
    
    // Check if HTTPS/Localhost is available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(rawText).then(function() {
            alert(\"Markdown copied to clipboard!\");
        }, function(err) {
            console.error(\"Could not copy text: \", err);
        });
        return;
    }
    
    // HTTP Fallback
    var textArea = document.createElement(\"textarea\");
    textArea.value = rawText;
    
    // Hide the textarea from view
    textArea.style.top = \"0\";
    textArea.style.left = \"0\";
    textArea.style.position = \"fixed\";
    textArea.style.opacity = \"0\";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            alert(\"Markdown copied to clipboard!\");
        } else {
            console.error(\"Fallback copy failed.\");
        }
    } catch (err) {
        console.error(\"Fallback copy errored: \", err);
    }
    
    document.body.removeChild(textArea);
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


function getxraycase(el,toppos)
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
    message.innerHTML = '<iframe id=\"framecase_'+elid+ '\" src=\"getxraycase.php?caseid='+ elid+ '&_=' + new Date().getTime()+'\" onload=\"this.style.height=this.contentWindow.document.body.scrollHeight+40 +\'px\';\"  style=\"width:100%\"></iframe>';
//    var iframe = document.getElementById('framecase_'+elid);
//    iframe.src = 'getxraycase.php?caseid='+ elid+'&_=' + new Date().getTime();
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
