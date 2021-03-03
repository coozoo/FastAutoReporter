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
    $runid="NULL";
    
/*    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['runid'])) {
        $runid=$query['runid'];
    }
    else
    {
	echo "required runid";
    }
*/    
    $SRVVERSION="";

 include("../mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
///////////////////////////////////////////////////
///////////////// get status by runid
///////////////////////////////////////////////////
    //if(isset($query['runid']))
    //{
    $getstatusquery="SELECT r_build_version FROM reporter.run where r_run_finish_date is not NULL AND team_id=2 AND environment_id=8 AND r_run_name='Soccer - Status of the match [111840]' order by r_run_start_date desc LIMIT 1;";
    //echo($getstatusquery);
    if($result = $mysqli->query($getstatusquery))
    {
        while($rows=mysqli_fetch_array($result)){
            if($rows[0])
	    {
		$SRVVERSION=$rows[0];
	    }
	    else
	    {
		$SRVVERSION="N/A";
	    }
        }
        $result->close();
        //$mysqli->next_result();
    }
    //}
    
echo $SRVVERSION;

CloseCon($mysqli);

?>
