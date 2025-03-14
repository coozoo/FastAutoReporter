<?php
//error_reporting(0);
//ini_set('display_errors', 0);


$myreporter = basename(dirname(__FILE__));
if (basename($_SERVER['DOCUMENT_ROOT']) == $myreporter) {
    $myreporter = "";
}
include($_SERVER['DOCUMENT_ROOT'] . "/$myreporter/initvar.php");

$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$query_str = parse_url($url, PHP_URL_QUERY);
$parts = parse_url($url);
$caseid = "NULL";

if (isset($parts['query'])) {
    parse_str($parts['query'], $query);
}
if (isset($query['caseid'])) {
    $caseid = $query['caseid'];
} else {
    exit;
}

$headers = array(
    "Authorization: Basic " . base64_encode("$jirauser:$jiratoken"),
    "Accept: application/json"
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$jirahost/rest/api/3/issue/$caseid");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$issue = json_decode($response, true);
if (!$issue || isset($issue['errorMessages'])) {
    exit;
}

$description = isset($issue['fields']['description']) ? $issue['fields']['description'] : array();
$attachments = isset($issue['fields']['attachment']) ? $issue['fields']['attachment'] : array();
$attachment_map = array();

foreach ($attachments as $attachment) {
    $attachment_map[$attachment['filename']] = $attachment['content'];
}


function parse_adf($content) {
    $html = "";
    if (!is_array($content)) {
        return $html;
    }
    foreach ($content as $block) {
        if (!is_array($block) || !isset($block['type'])) {
            continue;
        }
        if ($block['type'] == 'orderedList' && isset($block['content'])) {
            $html .= "<ol>";
            foreach ($block['content'] as $listItem) {
                $html .= "<li>" . parse_adf($listItem['content']) . "</li>";
            }
            $html .= "</ol>";
        } elseif ($block['type'] == 'paragraph' && isset($block['content'])) {
            $paragraphContent = "";
            foreach ($block['content'] as $inline) {
                if (isset($inline['marks'][0]['type']) && $inline['marks'][0]['type'] == 'strong') {
                    $paragraphContent .= "<strong>" . htmlspecialchars($inline['text']) . "</strong> ";
                } elseif ($inline['type'] == 'hardBreak') {
                    $paragraphContent .= "<br>";
                } else {
                    $paragraphContent .= isset($inline['text']) ? htmlspecialchars($inline['text']) : '';
                }
            }
            $html .= "<p>" . $paragraphContent . "</p>";
        } elseif ($block['type'] == 'mediaSingle' && isset($block['content'][0]['attrs']['alt'])) {
            $media = $block['content'][0]['attrs'];
            $image_data = fetch_jira_image($media['alt']);
            if ($image_data) {
                $html .= '<img src="' . $image_data . '" style="max-width:100%;"><br>';
            }
        } elseif (isset($block['content']) && is_array($block['content'])) {
            $html .= parse_adf($block['content']);
        }
    }
    return $html;
}

function fetch_jira_image($filename) {
    global $jirauser, $jiratoken, $attachment_map;
    if (!isset($attachment_map[$filename])) {
        return "";
    }
    $image_url = $attachment_map[$filename];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $image_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Basic " . base64_encode("$jirauser:$jiratoken"),
        "Accept: */*"
    ));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $image_data = curl_exec($ch);
    curl_close($ch);
    return "data:image/png;base64," . base64_encode($image_data);
}

function get_xray_jwt($project_id) {
    global $xraypluginpath, $jirahost, $jirauser, $jiratoken, $projectkey;

    $url = $jirahost . $xraypluginpath . "?classifier=json&project.id=" . urlencode($project_id) . "&project.key=" . urlencode($projectkey);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$jirauser:$jiratoken");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);

    if ($response === false) {
        error_log("Curl Error: " . $curl_error);
        die("Curl error: " . $curl_error);
    }

    curl_close($ch);
    $json_response = json_decode($response, true);
    if ($json_response === null) {
        error_log("JSON Decode Error: " . json_last_error_msg());
        die("Error: Invalid JSON Response");
    }

    if ($http_code !== 200 || (isset($json_response['status']) && $json_response['status'] != 200)) {
        error_log("Xray API Error: " . print_r($json_response, true));
        die("Error: Xray API returned an error: " . (isset($json_response['message']) ? $json_response['message'] : "Unknown error"));
    }

    // Extract JWT token
    if (isset($json_response['jwt'])) {
        return $json_response['jwt'];
    } elseif (isset($json_response['contextJwt'])) {
        return $json_response['contextJwt'];
    } else {
        error_log("JWT Token Not Found in Response: " . print_r($json_response, true));
        return null;
    }
}


function confluenceTablesToHtml($text) {
    return preg_replace_callback('/\|\|(.+?)\|\|\n((?:\|.+?\|(?:\n|$))+)/s', function ($matches) {
        // Extract headers
        $headers = explode('||', trim($matches[1]));
        $headersHtml = '<tr><th>' . implode('</th><th>', array_map('trim', $headers)) . '</th></tr>';

        // Extract rows
        $rows = preg_split('/\n/', trim($matches[2]), -1, PREG_SPLIT_NO_EMPTY);
        $rowsHtml = '';
        foreach ($rows as $row) {
            $cells = explode('|', trim($row, '|'));
            $rowsHtml .= '<tr><td>' . implode('</td><td>', array_map('trim', $cells)) . '</td></tr>';
        }

        return "<table border='1'>$headersHtml$rowsHtml</table>";
    }, $text);
}


function convertAtlassianMarkupToHtml($text) {
#echo($text);
$emojiMap = [
    '(x)' => '❌', // Red Cross Mark
    '(/)' => '☑️', // Ballot Box with Check
    '(+)' => '➕', // Plus
    '(-)' => '➖', // Minus
    '(on)' => '🔘', // Radio Button (On)
    '(off)' => '⚪', // Radio Button (Off)
    '(flag)' => '🚩', // Red Flag
    '(flagoff)' => '🏳️', // White Flag (Flag Off)
    '(!)' => '❗', // Exclamation Mark
    '(?)' => '❓', // Question Mark
    '(i)' => 'ℹ️', // Information
    '(*y)' => '⭐', // Yellow Star
    '(*b)' => '🔵', // Blue Circle
    '(*g)' => '&#128994', // Green Circle
    '(*r)' => '🔴'  // Red Circle
];

$text = str_replace(array_keys($emojiMap), array_values($emojiMap), $text);


 $text = confluenceTablesToHtml($text);
    $text = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $text);

    $text = preg_replace('/_(.*?)_/', '<em>$1</em>', $text);

    $text = preg_replace('/\+(.*?)\+/', '<u>$1</u>', $text);

    $text = preg_replace('/\{color:(#[0-9A-Fa-f]{6})\}(.*?)\{color\}/', '<span style="color:$1">$2</span>', $text);

$text = preg_replace('/h([1-6])\.(.*?)(?:\n|$)/', '<h$1>$2</h$1>', $text);
$text = preg_replace('/\{quote\}(.*?)\{quote\}/s', '<blockquote style="border-left: 4px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;">$1</blockquote>', $text);

$text = preg_replace('/\{noformat\}(.*?)\{noformat\}/s', 
    '<pre style="background: #f4f4f4; border: 1px solid #ccc; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;">$1</pre>', 
    $text);

    $text = preg_replace('/----/', '<hr>', $text);

    $text = preg_replace_callback('/(?:^|\n)[*] (.*?)(?=\n|$)/m', function ($matches) {
        return '<ul><li>' . trim($matches[1]) . '</li></ul>';
    }, $text);
    
    $text = preg_replace_callback('/(?:^|\n)[#] (.*?)(?=\n|$)/m', function ($matches) {
        return '<ol><li>' . trim($matches[1]) . '</li></ol>';
    }, $text);

    $text = preg_replace('/<\/ul>\s*<ul>/', '', $text);
    $text = preg_replace('/<\/ol>\s*<ol>/', '', $text);

    return nl2br(trim($text));
}


function get_project_id() {
    global $jirahost, $jirauser, $jiratoken, $projectkey;


    $url = "$jirahost/rest/api/3/project";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Basic " . base64_encode("$jirauser:$jiratoken"),
        "Accept: application/json"
    ));

    $response = curl_exec($ch);

    if ($response === false) {
        die("Curl error: " . curl_error($ch));
    }

    curl_close($ch);

    $projects = json_decode($response, true);

    if (!is_array($projects)) {
        error_log("JSON Parsing Error: Invalid response format");
        return null;
    }

    foreach ($projects as $project) {
        if (isset($project['key']) && $project['key'] === $projectkey) {
            return $project['id'];
        }
    }

    error_log("Project with key '$projectkey' not found.");
    return null;
}

function get_xray_teststeps( $x_acpt,$test_id) {
    global $xrayhost;

    $url = "$xrayhost/api/internal/test/$test_id/steps?startAt=0&maxResults=50";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0",
        "Accept: application/json, text/plain, */*",
        "X-acpt: $x_acpt"
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        die("Curl error: " . curl_error($ch));
    }

    curl_close($ch);

    return json_decode($response, true);
}


function replace_xray_attachments($text, $xray_jwt) {
    $modal_script = <<<HTML
    <script>
        function showXrayImage(src) {
            var modal = document.getElementById("xrayModal");
            var modalImg = document.getElementById("xrayModalImg");
            modal.style.display = "block";
            modalImg.src = src;
        }

        function closeXrayModal() {
            document.getElementById("xrayModal").style.display = "none";
        }
    </script>
    <div id="xrayModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.8); text-align:center;">
        <span onclick="closeXrayModal()" style="position:absolute; top:20px; right:30px; font-size:40px; font-weight:bold; color:white; cursor:pointer;">&times;</span>
        <img id="xrayModalImg" style="margin-top:5%; max-width:90%; max-height:90%;" alt="Xray Attachment">
    </div>
HTML;

    $text = preg_replace_callback('/!xray-attachment:\/\/([\w-]+)(?:\|width=(\d+),height=(\d+))?!/', function ($matches) use ($xray_jwt) {
        $attachment_id = $matches[1];
        $width = isset($matches[2]) ? (int)$matches[2] : 640;
        $height = isset($matches[3]) ? (int)$matches[3] : 480;

        // Enforce max width of 300px while maintaining aspect ratio
        if ($width > 300) {
            $height = intval(($height / $width) * 300);
            $width = 300;
        }

        $image_url = "xray_image.php?id=$attachment_id&xray_jwt=" . urlencode($xray_jwt);

        return "<br>
            <span onclick=\"showXrayImage('$image_url')\" style=\"cursor:pointer;\">
                <img src=\"$image_url\" width=\"$width\" height=\"$height\" style=\"max-width:300px; height:auto;\" alt=\"Xray Attachment\">
            </span>";
    }, $text);

    return $text . $modal_script; // Append modal HTML and script
}

function replace_xray_attachment_links($text, $xray_jwt) {
    return preg_replace_callback('/\[(.+?)\|xray-attachment:\/\/([\w-]+)\]/', function ($matches) use ($xray_jwt) {
        $filename = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $attachment_id = $matches[2];
        $image_url = "xray_image.php?id=$attachment_id&xray_jwt=" . urlencode($xray_jwt);

        return "<a href=\"$image_url\" target=\"_blank\">$filename</a>";
    }, $text);
}




$descriptionHtml = parse_adf(isset($description['content']) ? $description['content'] : array());
$issue_key = htmlspecialchars($issue['key']);
$issue_summary = htmlspecialchars($issue['fields']['summary']);
$issue_link = "$jirahost/browse/$issue_key";
$issue_numid=$issue['id'];
$projectid=get_project_id();
//echo($projectid);
$xray_jwt=get_xray_jwt($projectid);

//echo($xray_jwt);
sleep(0.1);
//echo($issue_numid);
$xray_steps = get_xray_teststeps($xray_jwt, $issue_numid);
//var_dump($xray_steps);
$cssTableStyleFile="csstablestyle.css";
$cssTableStyle=file_get_contents($cssTableStyleFile);

echo "<!DOCTYPE html><html>
<head><title>$issue_key - $issue_summary</title>   <link rel='shortcut icon' type='image/png' href=\"$iconfile\" />";
echo "<link rel='stylesheet' type='text/css' href='$cssTableStyleFile' />";
echo "      <style>
         pre {
            overflow-x: auto;
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;
            white-space: -o-pre-wrap;
            word-wrap: break-word;
         }
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            visibility: visible; /* Ensure it's visible immediately */
            opacity: 1;
            transition: opacity 0.3s ease-in-out; /* Smooth fade out */
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #ccc;
            border-top: 5px solid #000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

      </style>

<script>

        document.addEventListener(\"DOMContentLoaded\", function () {
            spinner_off(); // Hide as soon as DOM is loaded
        });

        setTimeout(spinner_off, 3000);
function spinner_on() {
  document.getElementById(\"overlay\").style.display = \"block\";
  document.getElementsByClassName('spinner')[0].style.display = \"block\";
}

function spinner_off() {
    var overlay=document.getElementById(\"overlay\");
    if (overlay !== null)
    {
	overlay.style.display = \"none\";
    }
    var spinner=document.getElementsByClassName('spinner')[0];
    if (spinner !== null)
    {
	spinner.style.display = \"none\";
    }
}</script>

</head><body>  <div id=\"overlay\">
  <!--<img src=\"img/anim/spinner.gif\" alt=\"progress\" class=\"image\">-->
</div>
<div class=\"spinner\"></div>";
echo "<div>";
echo "<h2><a href='$issue_link' target='_blank'>$issue_key</a> - $issue_summary</h2>";
echo "<div>$descriptionHtml</div>";

echo "<h3>Xray Steps</h3>";
echo "<table id=\"stepstable\" class=\"blueTable\"><thead><tr><th>Step</th><th>Action</th><th>Data</th><th>Result</th></tr></thead>";
foreach ($xray_steps['steps'] as $step) {
    echo "<tr>
	<td>" . $step['index'] . "</td>
        <td><pre>" . replace_xray_attachment_links(replace_xray_attachments(convertAtlassianMarkupToHtml(htmlspecialchars($step['action'])), $xray_jwt), $xray_jwt) . "</pre></td>
        <td>" . replace_xray_attachment_links(replace_xray_attachments(convertAtlassianMarkupToHtml(htmlspecialchars($step['data'])), $xray_jwt), $xray_jwt) . "</td>
        <td>" . replace_xray_attachment_links(replace_xray_attachments(convertAtlassianMarkupToHtml(htmlspecialchars($step['result'])), $xray_jwt), $xray_jwt) . "</td>
    </tr>";
}

echo "</table>";
echo "</div>";
echo "</body></html>";
?>

