<?php
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts = parse_url($url);
    $runid="NULL";
    $runuid="NULL";
    $featureview="true";
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['runid'])) {
        $runid=$query['runid'];
    }
    if (isset($query['runuid'])) {
        $runuid="'".$query['runuid']."'";
    }
    
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/suite.php");
?>
