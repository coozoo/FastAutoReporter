<?php
$myreporter = basename(dirname(__FILE__));
if (basename($_SERVER['DOCUMENT_ROOT']) == $myreporter) {
    $myreporter = "";
}
include($_SERVER['DOCUMENT_ROOT'] . "/$myreporter/initvar.php");

if (!isset($_GET['id']) || !isset($_GET['xray_jwt'])) {
    header("HTTP/1.0 400 Bad Request");
    exit("Missing parameters");
}

$attachment_id = $_GET['id'];
$xray_token = $_GET['xray_jwt'];

// The Official Xray Cloud Attachments Endpoint
$url = "$xrayhost/api/v2/attachments/" . urlencode($attachment_id);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 

// Tell cURL to include the headers in the output so we can parse them
curl_setopt($ch, CURLOPT_HEADER, 1); 
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $xray_token
]);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if (!$response) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Split the cURL response into headers and the binary image body
$raw_headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Extract headers from cURL response (YOUR EXACT LOGIC)
$headers = explode("\r\n", $raw_headers);
$filename = "image_$attachment_id.jpg"; // Default filename

// Extract filename from Content-Disposition
foreach ($headers as $header) {
    if (stripos($header, 'Content-Disposition:') !== false) {
        if (preg_match('/filename\*?=([^;]+)/i', $header, $matches)) {
            $filename = trim($matches[1], " \t\n\r\0\x0B\"");
            $filename = urldecode($filename);
        }
    }
}

// Fallback in case the API redirect doesn't pass the Content-Type
if (empty($content_type) || strpos($content_type, 'application/json') !== false) {
    $content_type = "image/png"; 
}

// Send headers and stream the image
header("Content-Type: $content_type");
// Using 'inline' so the browser displays it instead of forcing a download dialog
header("Content-Disposition: inline; filename=\"$filename\"");
header("Cache-Control: public, max-age=86400");
header("X-Content-Type-Options: nosniff");

// Stream the image body
echo $body;
exit;
?>
