<?php
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    $_script_started = microtime(1);
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php");
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts=parse_url($url);
    
    $testid=NULL;
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['testid'])) {
        $testid=$query['testid'];
    }
    else
    {
	echo "Required testid";
	exit;
    }

 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
    
//SELECT t_test_name,t_test_video,t_test_start_date FROM reporter.test where id=792266;
    if(isset($testid))
    {
	$getvideoinfo="SELECT t_test_name,t_test_video,t_test_start_date FROM reporter.test where id=$testid;";
    //echo($getlogsquery);
	if($result = $mysqli->query($getvideoinfo))
	{
    	    while($rows=mysqli_fetch_array($result)){
		//var_dump($rows);
        	$testname=$rows[0];
		$testvideoname=$rows[1];
		$teststartdate=$rows[2];
    	    }
        $result->close();
	}
    }


echo("<!DOCTYPE html><html lang=\"en\"><head><title>Test Video $testid $testname $teststartdate</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/>
    <meta charset=\"utf-8\">
    <style>
html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
}


body > div > video {
    width: 100%;
    background: grey;
    }    
</style>

</head><body>");

#echo "<video width=\"360\" height=\"250\" controls=\"controls\"> <source  src=\"http://vins207:8015/video/incorrectCredentialsLoginTest_[99e6fa3d-a1c8-4306-8866-d1319aa8f62d]_recording_2020_03_07_09_58_53.mp4\" type=\"video/mp4\"/>Your browser doesn't support this video</video>";
echo "<div id=\"container\"><video controls=\"controls\"> <source  src=\"/$myreporter/video/$testvideoname\" type=\"video/mp4\"/>Your browser doesn't support this video</video></div>";
echo("
</body></html>");
?>
