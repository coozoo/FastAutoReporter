<?php

    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
    $myreporter="";
    }
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php");

// getattachment.php?attachmentid=15 - show file in browser if it is safe type, otherwise download
// getattachment.php?attachmentid=15&download=true - always download

    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $parts = parse_url($url);
    $attachmentid=null;
    $download="false";

    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['attachmentid'])) {
        $attachmentid=$query['attachmentid'];
    }
    if (isset($query['download'])) {
        $download=$query['download'];
    }

if(is_null($attachmentid) || !ctype_digit((string)$attachmentid))
{
    http_response_code(400);
    echo "required numeric attachmentid";
    exit;
}

include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
$mysqli = OpenCon();

$row=null;
if($result = $mysqli->query("call get_attachment($attachmentid);"))
{
    $row=mysqli_fetch_assoc($result);
    $result->close();
    while($mysqli->more_results() && $mysqli->next_result()) {;}
}
else
{
    http_response_code(500);
    echo "Error: ".$mysqli->error;
    exit;
}
CloseCon($mysqli);

if(!$row)
{
    http_response_code(404);
    echo "attachment not found";
    exit;
}

$contenttype=($row['contenttype'])?$row['contenttype']:'application/octet-stream';
$basetype=strtolower(trim(explode(';',$contenttype)[0]));

// types browser can show are opened inline, others are downloaded
$inlinetypes=array('image/png','image/jpeg','image/gif','image/webp','image/bmp','image/svg+xml',
		    'text/plain','text/csv','text/html','text/xml','application/xml','application/json','application/pdf');
$inline=($download!="true" && in_array($basetype,$inlinetypes));

if(($basetype=='application/json' || strpos($basetype,'text/')===0) && stripos($contenttype,'charset')===false)
{
    $contenttype.="; charset=utf-8";
}

// ascii fallback name for old browsers, real name in filename*
$asciiname=preg_replace('/[^A-Za-z0-9._-]/','_',$row['filename']);

header("Content-Type: $contenttype");
header("Content-Length: ".$row['filesize']);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=86400");
header("Content-Disposition: ".($inline?"inline":"attachment")."; filename=\"$asciiname\"; filename*=UTF-8''".rawurlencode($row['filename']));
echo $row['content'];

?>
