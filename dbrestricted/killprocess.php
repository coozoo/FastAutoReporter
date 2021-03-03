<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    include("../initvar.php");
    
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts = parse_url($url);
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['processid'])) {
        $processid=$query['processid'];
    }
    else
    {
	echo "required processid";
	exit;
    }
    
    $RUNSTATUS="";

 include("../mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
///////////////////////////////////////////////////
///////////////// kill process by processid
///////////////////////////////////////////////////
    if(isset($query['processid']))
    {
    $killprocessquery="kill ".$processid.";";
    $mysqli->query($killprocessquery);
    /*if($result = $mysqli->query($getstatusquery))
    {
        while($rows=mysqli_fetch_array($result)){
            echo($rows[0]);
        }
        $result->close();
        //$mysqli->next_result();
    }*/
    }
    
?>
