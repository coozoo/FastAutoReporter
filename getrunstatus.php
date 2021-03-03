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
    $runid="NULL";
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['runid'])) {
        $runid=$query['runid'];
    }
    else
    {
	echo "required runid";
    }
    
    $RUNSTATUS="";

 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
///////////////////////////////////////////////////
///////////////// get status by runid
///////////////////////////////////////////////////
    if(isset($query['runid']))
    {
    $getstatusquery="SELECT r_is_run_finished as RUNID FROM reporter.run where run.id=".$runid.";";
    //echo($getstatusquery);
    if($result = $mysqli->query($getstatusquery))
    {
        while($rows=mysqli_fetch_array($result)){
            if($rows[0])
	    {
		$RUNSTATUS="Finished";
	    }
	    else
	    {
		$RUNSTATUS="InProgress";
	    }
        }
        $result->close();
        //$mysqli->next_result();
    }
    }
    
echo $RUNSTATUS;

CloseCon($mysqli);

?>
