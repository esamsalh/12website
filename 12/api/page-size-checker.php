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
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) { echo json_encode(['error' => $error]); exit; }
if ($httpCode !== 200) { echo json_encode(['error' => "HTTP $httpCode"]); exit; }

echo json_encode(['contents' => $html]);
