<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
if (!$url) {
    echo json_encode(['error' => 'missing_url', 'media' => []]);
    exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://backend1.tioo.eu.org/pinterest?url=' . urlencode($url),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['error' => 'no_media', 'media' => []]);
    exit;
}

$result = json_decode($response, true);
if (!$result || empty($result['success']) || empty($result['result'])) {
    echo json_encode(['error' => 'no_media', 'media' => []]);
    exit;
}

$data = $result['result'];
$media = [];
$seen = [];

$videos = [];
if (!empty($data['video_url'])) $videos[] = $data['video_url'];
if (!empty($data['videos']) && is_array($data['videos'])) {
    foreach ($data['videos'] as $v) {
        if (!empty($v['url'])) $videos[] = $v['url'];
    }
}

foreach ($videos as $v) {
    if (in_array($v, $seen)) continue;
    $seen[] = $v;
    if (preg_match('/\.m3u8/i', $v)) continue;
    $media[] = ['type' => 'video', 'url' => $v, 'thumbnail' => $data['image'] ?? '', 'title' => $data['title'] ?? ''];
}

if (empty($media) && !empty($data['image'])) {
    $media[] = ['type' => 'image', 'url' => $data['image'], 'thumbnail' => $data['image'], 'title' => $data['title'] ?? ''];
}

echo json_encode(['media' => $media]);
