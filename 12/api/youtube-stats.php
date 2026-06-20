<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$input = isset($_GET['channelId']) ? trim($_GET['channelId']) : '';
$apiKey = 'AIzaSyA7v0Kfq8gJKuA_FZXblFy9Hw2O0Q3hK1U';

if (!$input) { echo json_encode(['error' => 'no_channel_id']); exit; }

function fetchJson($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return $c == 200 ? json_decode($r, true) : null;
}

$channelId = '';
if (preg_match('/^UC[\w-]{22}$/', $input)) $channelId = $input;
elseif (preg_match('/youtube\.com\/channel\/(UC[\w-]+)/i', $input, $m)) $channelId = $m[1];
elseif (preg_match('/youtube\.com\/(?:user\/|c\/|@)([\w.-]+)/i', $input, $m)) {
    $username = $m[1];
    $data = fetchJson("https://www.googleapis.com/youtube/v3/channels?part=id&forUsername=" . urlencode($username) . "&key=" . urlencode($apiKey));
    if ($data && $data['items'][0]['id']) $channelId = $data['items'][0]['id'];
    else {
        $data = fetchJson("https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($username) . "&type=channel&maxResults=5&key=" . urlencode($apiKey));
        if ($data && $data['items']) $channelId = $data['items'][0]['snippet']['channelId'] ?? '';
    }
} else {
    $username = ltrim($input, '@');
    $data = fetchJson("https://www.googleapis.com/youtube/v3/channels?part=id&forUsername=" . urlencode($username) . "&key=" . urlencode($apiKey));
    if ($data && $data['items'][0]['id']) $channelId = $data['items'][0]['id'];
    else {
        $data = fetchJson("https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($username) . "&type=channel&maxResults=5&key=" . urlencode($apiKey));
        if ($data && $data['items']) $channelId = $data['items'][0]['snippet']['channelId'] ?? '';
    }
}

if (!$channelId) { echo json_encode(['error' => 'channel_not_found']); exit; }

$data = fetchJson("https://www.googleapis.com/youtube/v3/channels?part=statistics,snippet&id=" . urlencode($channelId) . "&key=" . urlencode($apiKey));
if (!$data || !$data['items'][0]) { echo json_encode(['error' => 'channel_not_found']); exit; }

$ch = $data['items'][0];
$stats = $ch['statistics'] ?? [];
$snippet = $ch['snippet'] ?? [];

echo json_encode([
    'channelId' => $ch['id'],
    'name' => $snippet['title'] ?? 'Unknown',
    'avatar' => $snippet['thumbnails']['medium']['url'] ?? $snippet['thumbnails']['default']['url'] ?? '',
    'subscriberCount' => (int)($stats['subscriberCount'] ?? 0),
    'viewCount' => (int)($stats['viewCount'] ?? 0),
    'videoCount' => (int)($stats['videoCount'] ?? 0),
    'hiddenSubscriberCount' => $stats['hiddenSubscriberCount'] ?? false,
    'publishedAt' => $snippet['publishedAt'] ?? ''
]);
