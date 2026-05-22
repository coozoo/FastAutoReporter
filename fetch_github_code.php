<?php
// fetch_github_code.php
header('Content-Type: text/plain');
require_once 'initvar.php';

$data = json_decode(file_get_contents('php://input'), true);
$queryTerm = isset($data['query']) ? $data['query'] : '';
$repo = isset($data['repo']) ? $data['repo'] : '';

if (empty($queryTerm) || empty($repo) || empty($copilot_pat)) {
    http_response_code(400);
    exit("Missing query, repo, or token.");
}

// Search INSIDE the files (in:file) for the test/method name
//$searchUrl = "https://api.github.com/search/code?q=" . urlencode($queryTerm) . "+in:file+repo:" . $repo;
$searchUrl = "https://api.github.com/search/code?q=" . urlencode($queryTerm) . "+in:file+-language:xml+-language:json+repo:" . $repo;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $searchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'QA-Dashboard');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $copilot_pat",
    "Accept: application/vnd.github.v3+json"
]);

$searchResponse = curl_exec($ch);
$searchData = json_decode($searchResponse, true);

// If we found the file, download the raw text
if (!empty($searchData['items'][0]['path'])) {
    $filePath = $searchData['items'][0]['path'];
    
    $rawUrl = "https://api.github.com/repos/$repo/contents/$filePath";
    curl_setopt($ch, CURLOPT_URL, $rawUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $copilot_pat",
        "Accept: application/vnd.github.v3.raw"
    ]);
    
    $rawCode = curl_exec($ch);
    curl_close($ch);
    
    echo $rawCode;
} else {
    curl_close($ch);
    http_response_code(404);
    echo "Code not found in repository.";
}
