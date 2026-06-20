<?php
$url = $_GET['url'] ?? '';
$type = $_GET['type'] ?? '';

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_REFERER => 'https://www.instagram.com/',
    CURLOPT_HTTPHEADER => ['Accept: */*', 'Accept-Language: en-US,en;q=0.9', 'Origin: https://www.instagram.com'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || $response === false) {
    http_response_code($httpCode ?: 500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch media', 'code' => $httpCode]);
    exit;
}

$ext = '.mp4';
$mime = 'video/mp4';
if ($type === 'image' || strpos($contentType ?: '', 'image') !== false) {
    $ext = '.jpg';
    $mime = $contentType ?: 'image/jpeg';
}

$filename = 'instagram_' . time() . $ext;
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($response));
header('Cache-Control: public, max-age=3600');
echo $response;
