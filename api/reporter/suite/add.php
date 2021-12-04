<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    include("../../../initvar.php");


/*
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts = parse_url($url);
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
*/
header('Content-Type: application/json');
$json = file_get_contents('php://input');

function show_help() {
    print "\nExample of usage:\n\n";
    print "POST DATA\n\n";
    print '{
    "runUid": "6C0ADDC8-504F-11EC-8724-B1E83179E075",
    "suiteName": "SomeLoginFlow",
    "suiteUid": "6C0ADDC8-504F-11EC-8724-B1E83179E075"
}
';
}

$json_obj = json_decode($json,true);


if(json_last_error() !== JSON_ERROR_NONE)
{
    printf("JSON Error: %s", json_last_error_msg());
    show_help();
    http_response_code(415);
    exit;
}

#echo $json_obj['testTeam'];

if(isset($json_obj['runUid']) && isset($json_obj['suiteUid']) &&
    isset($json_obj['suiteName']))
{
    //echo $json;
    include("../../../mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }

    $query="call add_suite('".$json_obj['runUid']."','".
				mysqli_real_escape_string($mysqli,$json_obj['suiteName'])."','".
				$json_obj['suiteUid']."');";
    //echo($query);
    if($result = $mysqli->query($query))
    {
        while($rows=mysqli_fetch_assoc($result)){
            $json_encoded=json_encode($rows);
        }
        $result->close();
    }
    else
    {
	printf("Error: %s\n", $mysqli->error);
	http_response_code(500);
    }

    if(strpos($json_encoded, 'MYSQL_ERROR') !== false)
    {
	print $json_encoded;
	http_response_code(500);
    }
    else
    {
	print $json_encoded;
    }

CloseCon($mysqli);

}
else
{
    print "Error: insufficient data\n";
    show_help();
    http_response_code(406);
}

?>
