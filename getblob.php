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
    $logid=null;
    $blob=null;
    $preview="false";
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['logid'])) {
        $logid=$query['logid'];
    }
    else
    {
    echo "required logid";
    }
    if (isset($query['preview'])) {
        $preview=$query['preview'];
    }
    
 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }


if(isset($query['logid']))
    {
    if($preview=="true")
    {
	$getblobquery="SELECT l_screenshot_preview FROM reporter.log where id=".$logid.";";
    }
    else
    {
	$getblobquery="SELECT l_screenshot_file_name FROM reporter.log where id=".$logid.";";
    }
    //echo($getblobquery);
    if($result = $mysqli->query($getblobquery))
    {
        while($rows=mysqli_fetch_array($result)){
        if($rows[0])
        {
	$blob=$rows[0];
        }
        }
        $result->close();
        //$mysqli->next_result();
    }
    }
header("Content-type: image/png");    
//echo '<img src="'.$blob.'" alt="HTML5 Icon" style="width:128px;height:128px">';;
echo $blob;

CloseCon($mysqli);

?>
