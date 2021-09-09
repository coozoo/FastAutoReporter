<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
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
    if(isset($parts['query']))
    {
	parse_str($parts['query'], $query);
    }
        $cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
    echo ("<!DOCTYPE html><html lang=\"en\"><head><title>System info</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
	<script src=\"sorttable.js\" type=\"text/javascript\"></script>
	</head><body>");

    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/header.php");
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if(!isset($mysqli))
    {
        echo "Connection failed";
    }
///////////////////////////////////////////////////
///////////////// get version
///////////////////////////////////////////////////
    //    echo($environmentsquery);
    $dbversionquery="SELECT VERSION();";
    if($result = $mysqli->query($dbversionquery))
    {
	while($rows=mysqli_fetch_array($result)){
	    $dbversion=$rows[0];
	}
	
	$result->close();
	//$mysqli->next_result();
    }
    echo("<b>DB version:</b> $dbversion");

    $tableinfoquery="SELECT 
			    TABLE_NAME as 'Table Name',
			    round(((data_length + index_length)/1024/1024), 2) 'Size in MB',
			    TABLE_SCHEMA as 'Schema',
			    ENGINE as 'Engine',
			    VERSION as 'Version',
			    ROW_FORMAT as 'Row Format',
			    TABLE_ROWS as 'Rows',
			    AVG_ROW_LENGTH 'Avg Row Length',
			    round(DATA_LENGTH/1024/1024,2) as 'Data Size in MB',
			    round(INDEX_LENGTH/1024/1024,2) as 'Index Size in MB',
			    round(DATA_FREE/1024/1024,2) as 'Free Size in MB',
			    AUTO_INCREMENT as 'Auto Increment',
			    CREATE_TIME as 'Create Time',
			    UPDATE_TIME as 'Update Time',
			    TABLE_COLLATION as 'Collation',
			    CREATE_OPTIONS as 'Options'
			    FROM information_schema.TABLES where TABLE_SCHEMA='reporter' order by `Size in MB` desc;";

    $tableinfo="<table id=\"tablebody\" class=\"blueTable sortable\">";
    $tableinfoheader="<thead><tr>";
    $tableinfobody="</tr></thead><tbody>";
    if ($result = $mysqli->query($tableinfoquery))
    {
	$finfo = $result->fetch_fields();
	$trcnt=0;
	$clmncnt=0;
	while($rows=mysqli_fetch_array($result)){
	    //echo "<tr id=\"PASS\">";
	    $tableinfobody.="<tr>";
	    foreach ($finfo as $val) {
		if($trcnt==0)
		{
		    //if($val->name!="RUNID")
		    //{
			$tableinfoheader.="<th>".$val->name."</th>";
		    //}
		}
//		if($val->name=="RUNID")
//		{
//		    $RUNID=$rows[$val->name];
//		}
//		else
//		{
//		    $tablebody.="<td><b><a href=\"suite.php?runid=".$RUNID."\" target=\"_blank\">".$rows[$val->name]."</a></b></td>";
		    $tableinfobody.="<td value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
//		}
	    }
	    $tableinfobody.="</tr>";
	    $trcnt++;
	}
	$clmncnt=substr_count($tableinfoheader,'<th>');
	//echo($clmncnt);
	
	$result->close();
	//$mysqli->next_result();
        $tableinfo.=$tableinfoheader.$tableinfobody;
	$tableinfo.="</tr></tbody></table>";
	
    }
    
    
        $tableeventinfoquery="select 
				EVENT_NAME as EventName,
				EVENT_SCHEMA as `Schema`,
				INTERVAL_VALUE as `Value`,
				INTERVAL_FIELD as `Interval`,
				LAST_EXECUTED as LastExecuted,
				EVENT_DEFINITION as EventDefinition,
				`STARTS` as `Starts`,
				`STATUS` as `Status`,
				CREATED as `Created`,
				LAST_ALTERED as LastAltered
				from INFORMATION_SCHEMA.EVENTS 
				where EVENT_SCHEMA='reporter' order by 1;";

    $tableeventinfo="<table id=\"eventbody\" class=\"blueTable sortable\">";
    $tableeventinfoheader="<thead><tr>";
    $tableeventinfobody="</tr></thead><tbody>";
    if ($result = $mysqli->query($tableeventinfoquery))
    {
	$finfo = $result->fetch_fields();
	$trcnt=0;
	$clmncnt=0;
	while($rows=mysqli_fetch_array($result)){
	    //echo "<tr id=\"PASS\">";
	    $tableeventinfobody.="<tr>";
	    foreach ($finfo as $val) {
		if($trcnt==0)
		{
			$tableeventinfoheader.="<th>".$val->name."</th>";
		}
		    $tableeventinfobody.="<td value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
	    }
	    $tableeventinfobody.="</tr>";
	    $trcnt++;
	}
	$clmncnt=substr_count($tableeventinfoheader,'<th>');
	//echo($clmncnt);
	
	$result->close();
	//$mysqli->next_result();
        $tableeventinfo.=$tableeventinfoheader.$tableeventinfobody;
	$tableeventinfo.="</tr></tbody></table>";
	
    }
    
echo("<h2>DB Processes List</h2><div id=\"processlist\">");
echo("</div>");
echo("<h2>DB Tables Info</h2>$tableinfo");
echo("<h2>DB Events Info</h2>$tableeventinfo");
echo("<h2>PHP Info</h2>");
echo('<a href="phpinfo.php" title="Detailed Info">Current PHP version: '.phpversion().'</a>');
echo("<script  type=\"text/javascript\">
updateprocessfunction();

function loadDoc() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) 
    {
		    var parser = new DOMParser();
		    var doc = parser.parseFromString(xhttp.responseText, \"text/html\");
		    var table=doc.getElementById(\"processtablebody\").outerHTML;
		    document.getElementById(\"processlist\").innerHTML=table;
    }
/*    else if (this.readyState != 4 && this.status != 200)
    {
    	//document.getElementById(targetcol).innerHTML=\"Error: \"+this.status +\" \" + this.readyState;
    	document.getElementById(\"processlist\").innerHTML=\"<font style='font-weight:bold;color:#5c95f7;'>Wait, loading...</font>\";
    }
    else if (this.readyState == 4 && this.status != 200)
    {
    	//document.getElementById(targetcol).innerHTML=\"Error: \"+this.status +\" \" + this.readyState;
    	document.getElementById(\"processlist\").innerHTML=\"<font style='font-weight:bold;color:#f77b5c;'>Oops, something wrong...</font>\";
    }*/
    };
	xhttp.open(\"GET\", \"dbprocesslist.php?_=\" + new Date().getTime(), true);
	xhttp.send();
	
}

function killprocess(processid) {
    if (confirm('Killing DB Process: ' + processid)) {
	fetch(\"dbrestricted/killprocess.php?processid=\"+processid);
	loadDoc();
    } 
    //else {
        // Do nothing!
    //    }
}
    
function updateprocessfunction()
{
	loadDoc();
	console.log(\"updateprocess\");
	setTimeout(function(){updateprocessfunction();},10000);
}

</script>");
    
echo("</body></html>");
?>
