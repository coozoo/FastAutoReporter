<?php
basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
    $myreporter="";
    }
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php");
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);

$attachment_id ="";
$xray_jwt="";
    $parts = parse_url($url);
    if(isset($parts['query']))
    {
	parse_str($parts['query'], $query);
    }
    if(isset($query['id']))
    {
	$attachment_id=$query['id'];
    }
else
{
    http_response_code(400);
    die("Missing id");
}
    if(isset($query['xray_jwt']))
    {
	$xray_jwt=$query['xray_jwt'];
    }
else
{
    http_response_code(400);
    die("Missing xray_jwt");
}

//echo "Attachment ID: $attachment_id\n";
//echo "Xray JWT: $xray_jwt\n";
//exit;



$xray_url = "$xrayhost/api/internal/attachments/$attachment_id?jwt=$xray_jwt";


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $xray_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Stream directly to output
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);  // Fetch headers
curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0",
    "Accept: image/avif,image/webp,image/png,image/svg+xml,image/*;q=0.8,*/*;q=0.5"
]);

// Execute request and capture headers
ob_start();
$fp = fopen('php://output', 'wb');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);
fclose($fp);

// Extract headers from cURL response
$raw_headers = ob_get_clean();
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

// Send headers and stream the image
header("Content-Type: $content_type");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: public, max-age=86400");
header("X-Content-Type-Options: nosniff");
fpassthru(fopen($xray_url, 'rb'));
exit;
