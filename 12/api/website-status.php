<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
if (empty($url)) { echo json_encode(['error' => 'URL is required']); exit; }
if (!filter_var($url, FILTER_VALIDATE_URL)) { echo json_encode(['error' => 'Invalid URL']); exit; }

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => false, CURLOPT_HEADER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME_T);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$sslResult = [];
if (strpos($url, 'https://') === 0) {
    $certInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
    $sslResult = $certInfo && isset($certInfo[0])
        ? ['valid' => true, 'issuer' => $certInfo[0]['Issuer'] ?? 'Unknown', 'subject' => $certInfo[0]['Subject'] ?? 'Unknown', 'expiry' => $certInfo[0]['Expire date'] ?? 'Unknown', 'start' => $certInfo[0]['Start date'] ?? 'Unknown']
        : ['valid' => true, 'issuer' => 'Unknown', 'note' => 'Certificate info not available'];
}
curl_close($ch);

if ($error && !$response) { echo json_encode(['error' => $error]); exit; }

$headersStr = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$server = ''; $contentType = '';
foreach (explode("\r\n", $headersStr) as $line) {
    if (stripos($line, 'Server:') === 0) $server = trim(substr($line, 7));
    if (stripos($line, 'Content-Type:') === 0) $contentType = trim(substr($line, 13));
}

echo json_encode(['contents' => $body, 'http_status' => $httpCode, 'server' => $server, 'content_type' => $contentType, 'time_ms' => round($totalTime / 1000), 'ssl' => $sslResult]);
