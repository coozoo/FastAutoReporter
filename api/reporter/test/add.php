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
    "feature": "Login",
    "logLines": [
        "this log line - ==================== TESTRAIL REPORT ====================",
        "2021-11-27 19:02:16 - [INFO]:[Th167] - RunName: blabla",
        "2021-11-27 19:02:16 - [INFO]:[Th167] - ReportURL: http://testrail.com/index.php?/plans/view/154767",
        "2021-11-27 19:02:20 - [INFO]:[Th167] - LoadingPage remained open for 2 sec",
        "[FONT-WEIGHT:bold;COLOR:Green]2021-11-27 19:03:05 - [PASS]:[Th167] - Login Pass",
        "[FONT-WEIGHT:bold;COLOR:Red]2021-11-27 19:03:05 - [FAIL]:[Th167] - [590ab7cc-3190-4895-90f7-3ed0aa9a4009]Login Failed",
        "2021-11-27 19:03:10 - [INFO]:[Th167] - JVM MEMORY: Free [1643 Mb]. Used [3246 Mb]"
    ],
    "screenshotFiles": [
        {
            "screenshotBase64": "Base64DataiVBORw0KGgoAAAANSUhEUgAAB4AA=",
            "screenshotPreviewBase64": "Base64Data=",
            "screenshotUid": "590ab7cc-3190-4895-90f7-3ed0aa9a4009",
	    "screenshotType": "text/html; charset=utf-8"
        }
    ],
    "suiteUid": "00614A1E-5057-11EC-9DE9-99FC3179E075",
    "testAuthor": "John Doe",
    "testDuration": 4002,
    "testFinishDate": "2021-11-25T08:37:24.634+0000",
    "testName": "LoginTest",
    "testResult": "PASS",
    "testStartDate": "2021-11-25T08:37:20.632+0000",
    "testUid": "00614A1E-5057-11EC-9DE9-99FC3179E075",
    "testrailId": "C4452748",
    "jiraId":"JIR-123",
    "xrayId":"JIR-222"
}

INFO

*logLines - array of loglines, it can contain css style to highlight row inside report;
*screenshotUid - this uid should be present in appropriate logline inside square brackets;
*screenshotType - if not present default is png;
*testDuration - duration of test in ms;
*testResult - PASS, FAIL, SKIP, ERROR;
*testrailId - id of case in testrail, you need to setup testrail user password to use this feature;
*jiraId - jira issue id related to testcase;
*xrayId - xray cloud issue id.
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

if(isset($json_obj['suiteUid']) && isset($json_obj['testUid']) &&
    isset($json_obj['testName']) && isset($json_obj['feature']) &&
    isset($json_obj['testResult']) && isset($json_obj['testAuthor']) &&
    isset($json_obj['testStartDate']) && isset($json_obj['testFinishDate']))
{
    //echo $json;
    include("../../../mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
/* can be null
t_testrail_id
t_xray_id
t_defect
t_test_run_duration
t_additional_info
test_author_id
t_test_video
t_jira_id
*/

    $query="call add_test_v2(".((isset($json_obj['additionalInfo']))?"'".mysqli_real_escape_string($mysqli,$json_obj['additionalInfo'])."'":'NULL').",".
				((isset($json_obj['defect']))?"'".mysqli_real_escape_string($mysqli,$json_obj['defect'])."'":'NULL').",'".
				mysqli_real_escape_string($mysqli,$json_obj['feature'])."',".
				((isset($json_obj['jiraId']))?"'".mysqli_real_escape_string($mysqli,$json_obj['jiraId'])."'":'NULL').",'".
				$json_obj['suiteUid']."',".
				((isset($json_obj['testAuthor']))?"'".mysqli_real_escape_string($mysqli,$json_obj['testAuthor'])."'":'NULL').",".
				((isset($json_obj['testDuration']))?$json_obj['testDuration']:'NULL').",'".
				preg_replace('/\\+\d+/','',$json_obj['testFinishDate'])."','".
				mysqli_real_escape_string($mysqli,$json_obj['testName'])."','".
				$json_obj['testResult']."','".
				preg_replace('/\\+\d+/','',$json_obj['testStartDate'])."','".
				$json_obj['testUid']."',".
				((isset($json_obj['testVideoFileName']))?"'".mysqli_real_escape_string($mysqli,$json_obj['testVideoFileName'])."'":'NULL').",".
				((isset($json_obj['testrailId']))?"'".mysqli_real_escape_string($mysqli,$json_obj['testrailId'])."'":'NULL').",".
				((isset($json_obj['xrayId']))?"'".mysqli_real_escape_string($mysqli,$json_obj['xrayId'])."'":'NULL').");";
//    call add_test('additionalInfo','defect','feature','jiraId','00614A1E-5057-11EC-9DE9-99FC3179E075','testAuthor',1000,date_add(now(),interval 2 minute),'testName','PASS',now(),'testUid','testVideoFileName','testrailId','xrayId');

    //echo($query);
    if($result = $mysqli->query($query))
    {
        while($rows=mysqli_fetch_assoc($result)){
            $json_encoded=json_encode($rows);
	    $json_decoded=$rows;
        }
        $result->close();
	//$mysqli->next_result();
	while ($mysqli->next_result()) {;}
	if(!isset($json_decoded['MYSQL_ERROR']))
	{
	    $multiquery="";
	    if(isset($json_obj['logLines']) && count($json_obj['logLines'])>0)
	    {
		$arrayofscreenshots=array();
		if(isset($json_obj['screenshotFiles']) && count($json_obj['screenshotFiles'])>0)
		{
		    foreach($json_obj['screenshotFiles'] as $screenshotObj)
		    {
			//print $screenshotObj['screenshotUid'];
			$i=0;
			foreach($json_obj['logLines'] as $logline)
			{
			    if(strpos($logline,"[".$screenshotObj['screenshotUid']."]")!== false)
			    {
				//print $i;
				$arrayofscreenshots += [$i => $screenshotObj];
			    }
			    ++$i;
			}
		    }
		}
		//print var_dump($arrayofscreenshots);
		$i=0;
		foreach($json_obj['logLines'] as $logline)
		{
		    if(array_key_exists($i,$arrayofscreenshots))
		    {
			//print "key is here $i";
			//print $arrayofscreenshots[$i]['screenshotUid'];
			//print str_replace("[".$arrayofscreenshots[$i]['screenshotUid']."]", "", $logline)."\n";
			$multiquery.="insert into log (l_test_id,l_log, l_screenshot_type, l_screenshot_file_name, l_screenshot_preview) values (".$json_decoded['test_id'].",'".mysqli_real_escape_string($mysqli,str_replace("[".$arrayofscreenshots[$i]['screenshotUid']."]", "", $logline))."',".((isset($arrayofscreenshots[$i]['screenshotType']))?"'".mysqli_real_escape_string($mysqli,$arrayofscreenshots[$i]['screenshotType'])."'":'NULL').",'".mysqli_real_escape_string($mysqli,base64_decode($arrayofscreenshots[$i]['screenshotBase64']))."','".mysqli_real_escape_string($mysqli,base64_decode($arrayofscreenshots[$i]['screenshotPreviewBase64']))."');";
//				    set @lastlogid=(SELECT LAST_INSERT_ID());
//				    insert into test_log (test_id, log_id) values (".$json_decoded['test_id'].", @lastlogid);";
			//print $multiquery1;
		    }
		    else //if($i==0)
		    {
			//print $logline."\n";
			$multiquery.="insert into log (l_test_id,l_log) values (".$json_decoded['test_id'].",'".mysqli_real_escape_string($mysqli,$logline)."');";
//				    set @lastlogid=(SELECT LAST_INSERT_ID());
//			 	    insert into test_log (test_id, log_id) values (".$json_decoded['test_id'].", @lastlogid);";
		    }
		    ++$i;
		}
	    }
	    //$multiquery.="";
	    //$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
	    //fwrite($myfile, $multiquery);
	    //print $multiquery;
	    $mysqli->autocommit(FALSE);
	    if ($mysqli->multi_query($multiquery))
	    {
		//print "logs added";
		while ($mysqli->next_result()) {;}
		$mysqli->commit();
	    }
	    else
	    {
		    echo "Multi query failed: (" . $mysqli->errno . ") " . $mysqli->error;
		    http_response_code(500);
	    }
	    
	    
	}
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
