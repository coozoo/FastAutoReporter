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
    if(isset($parts['query']))
    {
	parse_str($parts['query'], $query);
    }
    $cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
    echo ("<!DOCTYPE html><html lang=\"en\"><head><title>System info</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
	<script src=\"sorttable.js\" type=\"text/javascript\"></script>
	</head><body>");
    

//    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/header.php");
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if(!isset($mysqli))
    {
        echo "Connection failed";
    }

    $tableinfoquery="SELECT 
			    `ID` as `ID`,
			    `USER` as `User`,
			    `HOST` as `Host`,
			    `DB` as `DB`,
			    `COMMAND` as `Command`,
			    TIME_FORMAT(SEC_TO_TIME(`TIME`),\"%H:%i:%s\") as `Time`,
			    `STATE` as `State`,
			    `INFO` as `Info` 
			FROM INFORMATION_SCHEMA.PROCESSLIST 
			where DB='reporter' order by `State` desc,`TIME` desc;";

    $tableprocess="<table id=\"processtablebody\" class=\"blueTable sortable\">";
    $tableprocessheader="<thead><tr>";
    $tableprocessbody="</tr></thead><tbody>";
    if ($result = $mysqli->query($tableinfoquery))
    {
	$finfo = $result->fetch_fields();
	$trcnt=0;
	$clmncnt=0;
	while($rows=mysqli_fetch_array($result)){
	    //echo "<tr id=\"PASS\">";
	    $tableprocessbody.="<tr>";
	    foreach ($finfo as $val) {
		if($trcnt==0)
		{
		    //if($val->name!="RUNID")
		    //{
			$tableprocessheader.="<th>".$val->name."</th>";
		    //}
		}
		if($val->name=="ID")
		{
		    //$tableprocessbody=$tableprocessbody."<td><b><a href=\"dbrestricted/killprocess.php?processid=".$rows[$val->name]."\" target=\"_blank\">".$rows[$val->name]."</a></b></td>";
		    $tableprocessbody.="<td><font style=\"cursor:pointer;text-decoration:underline;font-weight:bold;\" onclick=\"return killprocess(".$rows[$val->name].");\" title=\"Kill Process\">".$rows[$val->name]."</font></td>";
		}
		elseif($val->name=="Info")
		{
		    $tableprocessbody.="<td><code>".htmlentities($rows[$val->name])."</code></td>";
		}
		else
		{
		    $tableprocessbody.="<td value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
		}
	    }
	    $tableprocessbody.="</tr>";
	    $trcnt++;
	}
	$clmncnt=substr_count($tableprocessheader,'<th>');
	//echo($clmncnt);
	
	$result->close();
	//$mysqli->next_result();
        $tableprocess.=$tableprocessheader.$tableprocessbody;
	$tableprocess.="</tr></tbody></table>";
	echo("$tableprocess");
    }
    
    echo("<script  type=\"text/javascript\">

//var envselection = document.getElementById('envselection');


function killprocess(processid) {
    if (confirm('Killing DB Process: ' + processid)) {
	fetch(\"dbrestricted/killprocess.php?processid=\"+processid);
    } 
    //else {
        // Do nothing!
    //    }
    
}
</script>");
    
echo("</body></html>");
?>
