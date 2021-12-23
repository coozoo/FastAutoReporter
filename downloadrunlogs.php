<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    require_once('stuff/zip.lib.php');
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
	echo "runid required";
	exit;
    }
    
//    ini_set('max_execution_time', '360');
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
	$getlogsquery="select octet_length(l_log) as bytes, l_log
			    from `reporter`.`run`,
				`reporter`.`suite`,
				`reporter`.`test`,
				`reporter`.`log`
			    where  `run`.`id`=".$runid."
				AND (reporter.suite.s_run_id=reporter.run.`id` AND
				`reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id`  AND
				`reporter`.`log`.`l_test_id` = `reporter`.`test`.`id`);";
    //echo($getlogsquery);
    $size=0;
    $logsdata="";
//    ini_set('memory_limit', '-1');
    if($result = $mysqli->query($getlogsquery))
    {
	$logsarray=array();
        while($rows=mysqli_fetch_array($result)){
            $size=$size+$rows[0];
	    array_push($logsarray,$rows[1]);
	    //$logsdata=$logsdata.$rows[1]."\n";
        }
	$logsdata=join( "\n", $logsarray);
        $result->close();
        //$mysqli->next_result();
    }
    }

/*uncompressed file*/
//    header("Content-Disposition: attachment; filename=\"$runid\".txt");
//    header("Content-type: application/text");
//    header("Content-length: $size");
//    print $logsdata;

/*compressed file*/
    $zip = new zipfile();
    $zip->addFile($logsdata, $runid.'.txt');

    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=$runid.zip");
    $start_memory = memory_get_usage();
    $zipfile=$zip->file();
    //header("Content-length: ".(memory_get_usage() - $start_memory));

    echo $zipfile;

CloseCon($mysqli);

?>