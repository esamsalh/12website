<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$url = $_GET['url'] ?? '';
$format = $_GET['format'] ?? 'mp3';
$quality = $_GET['quality'] ?? '320';
if (!$url) { echo json_encode(['success' => false, 'message' => 'الرجاء إدخال رابط فيديو يوتيوب']); exit; }

function getYouTubeId($str) {
    preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/|youtube-nocookie\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $str, $m);
    return $m[1] ?? null;
}

$videoId = getYouTubeId($url);
if (!$videoId) { echo json_encode(['success' => false, 'message' => 'رابط يوتيوب غير صالح']); exit; }

$apiUrl = 'https://youtube-to-mp3-converter2.p.rapidapi.com/?' . http_build_query([
    'url' => 'https://www.youtube.com/watch?v=' . $videoId,
    'id' => $videoId, 'format' => $format, 'quality' => $quality
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'x-rapidapi-key: 6d4244b945msh051ffbb761dd90ep160e4bjsne8888f0e3c9b',
        'x-rapidapi-host: youtube-to-mp3-converter2.p.rapidapi.com',
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) { echo json_encode(['success' => false, 'message' => 'تعذر الاتصال بخادم التحويل: ' . $curlError]); exit; }
if ($httpCode !== 200) { echo json_encode(['success' => false, 'message' => 'خادم التحويل غير متاح حالياً (رمز الخطأ: ' . $httpCode . ')']); exit; }

$data = json_decode($response, true);
if (isset($data['messages']) && strpos($data['messages'], 'unreachable') !== false) {
    echo json_encode(['success' => false, 'message' => 'عذراً، خادم RapidAPI غير متاح حالياً. يرجى استخدام روابط التحميل البديلة بالأسفل.']);
    exit;
}

$downloadUrl = '';
if ($data) {
    foreach (['link', 'url', 'download', 'downloadUrl', 'mp3', 'audio', 'download_url', 'href'] as $key) {
        if (!empty($data[$key]) && is_string($data[$key]) && filter_var($data[$key], FILTER_VALIDATE_URL)) {
            $downloadUrl = $data[$key]; break;
        }
    }
}

if ($downloadUrl) {
    echo json_encode(['success' => true, 'downloadUrl' => $downloadUrl, 'title' => $data['title'] ?? 'YouTube Audio', 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'لم نتمكن من الحصول على رابط تحميل مباشر. يرجى استخدام الروابط البديلة بالأسفل.', 'data' => $data]);
}
