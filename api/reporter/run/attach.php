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
    // default for servers with older initvar.php
    if(!isset($attachmentMaxBytes))
    {
	$attachmentMaxBytes=10485760;
    }

header('Content-Type: application/json');

$json = file_get_contents('php://input');

function show_help() {
    global $attachmentMaxBytes;
    print "\nExample of usage:\n\n";
    print "POST DATA\n\n";
    print '{
    "runId": 3841,
    "files": [
        {
            "fileName": "summary.json",
            "contentType": "application/json",
            "contentBase64": "eyJwYXNzZWQiOjEwfQ=="
        },
        {
            "fileName": "screen.png",
            "contentType": "image/png",
            "contentBase64": "iVBORw0KGgoAAAANSUhEUgAAB4AA="
        }
    ]
}

RESPONSE

{"runId":3841,"attachmentIds":[15,16]}

INFO

*runId - id of run returned by run/add (run_id);
*runUid - can be used instead of runId for compatibility, runId is preferred;
*files - array of files, all files are added or none of them;
*fileName - original file name (max 255 chars);
*contentType - mime type (max 127 chars), if not present default is application/octet-stream;
*contentBase64 - file content in base64, max size of one decoded file is '.$attachmentMaxBytes.' bytes.
';
}

// characters, not bytes (mbstring may be missing)
function charcount($s) {
    return preg_match_all('/./us', $s);
}

function fail($code, $message) {
    http_response_code($code);
    print "Error: $message\n";
    if($code==406)
    {
	show_help();
    }
    exit;
}

$json_obj = json_decode($json,true);
unset($json);

if(json_last_error() !== JSON_ERROR_NONE)
{
    http_response_code(415);
    printf("JSON Error: %s", json_last_error_msg());
    show_help();
    exit;
}

if(!isset($json_obj['files']) || !is_array($json_obj['files']) || count($json_obj['files'])==0 ||
    (!isset($json_obj['runId']) && !isset($json_obj['runUid'])))
{
    fail(406, "insufficient data");
}

if(isset($json_obj['runId']) && !preg_match('/^\d+$/', (string)$json_obj['runId']))
{
    fail(406, "runId must be a number");
}

include("../../../mysqli_connection.php");
$mysqli = OpenCon();

// runId is used as is, runUid is resolved to id only when runId is absent
if(isset($json_obj['runId']))
{
    $runid=$json_obj['runId'];
}
else
{
    $runid=null;
    $query="select id from run where r_run_uid='".mysqli_real_escape_string($mysqli,$json_obj['runUid'])."';";
    if($result = $mysqli->query($query))
    {
	if($row=mysqli_fetch_assoc($result))
	{
	    $runid=$row['id'];
	}
	$result->close();
    }
    else
    {
	fail(500, $mysqli->error);
    }
    if(is_null($runid))
    {
	fail(404, "run not found");
    }
}

$attachmentids=array();
$mysqli->autocommit(FALSE);

$i=0;
foreach($json_obj['files'] as $file)
{
    $error=null;
    if(!isset($file['fileName']) || $file['fileName']=="" || !isset($file['contentBase64']))
    {
	$error="fileName and contentBase64 are required";
    }
    elseif(charcount($file['fileName'])>255)
    {
	$error="fileName is longer than 255 chars";
    }
    elseif(isset($file['contentType']) && charcount($file['contentType'])>127)
    {
	$error="contentType is longer than 127 chars";
    }
    // rough check by base64 length (10% room for line breaks) to not decode huge files, exact check is after decoding
    elseif(strlen($file['contentBase64'])>$attachmentMaxBytes*4/3*1.1+4)
    {
	$error="file is bigger than $attachmentMaxBytes bytes";
    }
    else
    {
	$content=base64_decode($file['contentBase64'], true);
	if($content===false)
	{
	    $error="contentBase64 is not valid base64";
	}
	elseif(strlen($content)>$attachmentMaxBytes)
	{
	    $error="file is bigger than $attachmentMaxBytes bytes";
	}
    }
    if(!is_null($error))
    {
	$mysqli->rollback();
	fail(406, "files[$i] $error");
    }

    $contenttype=(isset($file['contentType']) && $file['contentType']!="")?$file['contentType']:'application/octet-stream';
    $query="call add_attachment(".$runid.",'".
				mysqli_real_escape_string($mysqli,$file['fileName'])."','".
				mysqli_real_escape_string($mysqli,$contenttype)."','".
				mysqli_real_escape_string($mysqli,$content)."');";
    unset($content);
    $row=null;
    if($result = $mysqli->query($query))
    {
	$row=mysqli_fetch_assoc($result);
	$result->close();
	while($mysqli->more_results() && $mysqli->next_result()) {;}
    }
    else
    {
	$dberror=$mysqli->error;
	$mysqli->rollback();
	fail(500, $dberror);
    }
    unset($query);

    if(isset($row['MYSQL_ERROR']))
    {
	$mysqli->rollback();
	if($row['MYSQL_ERROR']==1452)
	{
	    fail(404, "run not found");
	}
	fail(500, "files[$i] MYSQL_ERROR ".$row['MYSQL_ERROR']);
    }
    $attachmentids[]=(int)$row['attachment_id'];
    ++$i;
}

if(!$mysqli->commit())
{
    fail(500, $mysqli->error);
}
CloseCon($mysqli);

print json_encode(array("runId" => (int)$runid, "attachmentIds" => $attachmentids));

?>
