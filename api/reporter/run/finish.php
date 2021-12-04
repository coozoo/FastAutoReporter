<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    include("../../../initvar.php");



    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts = parse_url($url);
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if(isset($query['runuid']))
    {
	print "match";
	$runuid=$query['runuid'];
    }
    else
    {
	print("Please provide runuid");
	die(0);
    }

    include("../../../mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }

    $query="update reporter.run set r_is_run_finished=1 where r_run_uid='$runuid';";
    //echo($query);
    if($result = $mysqli->query($query))
    {
        //while($rows=mysqli_fetch_assoc($result)){
        //    $json_encoded=json_encode($rows);
        //}
        //$result->close();
	printf("Run '%s' was finished successfully",$runuid);
    }
    else
    {
	printf("Error: %s\n", $mysqli->error);
	http_response_code(500);
    }




CloseCon($mysqli);


?>
