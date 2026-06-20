<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST only']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$prompt = trim($input['prompt'] ?? '');
$maxTokens = min((int)($input['maxTokens'] ?? 300), 1000);
if (!$prompt) { http_response_code(400); echo json_encode(['error' => 'يرجى إدخال النص المطلوب']); exit; }

$ch = curl_init('https://gemma3.cc/api/chat');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: text/event-stream', 'User-Agent: Mozilla/5.0'],
    CURLOPT_POSTFIELDS => json_encode(['messages' => [['role' => 'user', 'content' => $prompt]], 'model' => 'gemma-3-12b']),
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) { http_response_code(502); echo json_encode(['error' => 'خطأ في الاتصال: ' . $curlError]); exit; }
if ($httpCode !== 200) { http_response_code($httpCode); echo json_encode(['error' => 'خطأ من الخادم ('.$httpCode.')']); exit; }

$fullText = '';
foreach (explode("\n", $response) as $line) {
    $line = trim($line);
    if (str_starts_with($line, '0:')) {
        $decoded = json_decode(substr($line, 2));
        if (is_string($decoded)) $fullText .= $decoded;
    }
}

$fullText = trim($fullText);
if (!$fullText) $fullText = 'لم يتم إنشاء نص. حاول بصياغة مختلفة.';
echo json_encode(['text' => $fullText], JSON_UNESCAPED_UNICODE);
