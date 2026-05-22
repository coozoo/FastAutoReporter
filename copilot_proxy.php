<?php
// Set headers so JS knows it's receiving JSON back
header('Content-Type: application/json');

// 1. Read the incoming request from your JS
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['apiKey']) || !isset($data['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request: Missing apiKey or messages']);
    exit;
}

$vimToken = $data['apiKey'];
$messages = $data['messages'];
$model = isset($data['model']) ? $data['model'] : 'gpt-4o';

// ======================================================================
// STEP 1: Exchange the Vim OAuth token for a temporary Copilot Session Token
// ======================================================================
$chToken = curl_init('https://api.github.com/copilot_internal/v2/token');
curl_setopt($chToken, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chToken, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $vimToken,
    'Editor-Version: vscode/1.85.0',
    'Editor-Plugin-Version: copilot/1.138.0',
    'User-Agent: GithubCopilot/1.138.0'
]);

$tokenResponse = curl_exec($chToken);
$tokenHttpCode = curl_getinfo($chToken, CURLINFO_HTTP_CODE);
curl_close($chToken);

if ($tokenHttpCode !== 200) {
    http_response_code($tokenHttpCode);
    echo json_encode(['error' => 'Failed to get Copilot Session Token. Is your Vim token valid?', 'details' => json_decode($tokenResponse)]);
    exit;
}

$tokenData = json_decode($tokenResponse, true);
if (!isset($tokenData['token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid token format received from GitHub.', 'details' => $tokenData]);
    exit;
}
$sessionToken = $tokenData['token']; // The massive temporary JWT

// ======================================================================
// STEP 2: Call the Enterprise Chat API using the Session Token
// ======================================================================
$payload = json_encode([
    'model' => $model,
    'messages' => $messages,
    'temperature' => 0.1
]);

// Make the Server-to-Server request (Bypasses CORS completely!)
$chChat = curl_init('https://api.enterprise.githubcopilot.com/chat/completions');
curl_setopt($chChat, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chChat, CURLOPT_POST, true);
curl_setopt($chChat, CURLOPT_POSTFIELDS, $payload);
curl_setopt($chChat, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $sessionToken,
    'Editor-Version: vscode/1.85.0', // Fakes an IDE editor so the API accepts it
    'User-Agent: GithubCopilotProxy/1.0'
]);

$chatResponse = curl_exec($chChat);
$chatHttpCode = curl_getinfo($chChat, CURLINFO_HTTP_CODE);

if(curl_errno($chChat)) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL Error: ' . curl_error($chChat)]);
} else {
    http_response_code($chatHttpCode);
    echo $chatResponse;
}

curl_close($chChat);
?>
