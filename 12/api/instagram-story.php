<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
if (!$username) { die(json_encode(['success' => false, 'message' => 'الرجاء إدخال اسم المستخدم', 'items' => []])); }
$username = preg_replace('/[^a-zA-Z0-9_.]/', '', $username);
if (strlen($username) < 2) { die(json_encode(['success' => false, 'message' => 'اسم المستخدم غير صالح', 'items' => []])); }

function fetchUrl($url, $headers = [], $post = false, $postData = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_COOKIE => 'ig_did=AE0A2D8E-2F9C-4B97-8A1E-3C5D6E7F8A9B; mid=Z_3X4gALAAF3FJh6p3F6G3F7H8I9',
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => array_merge(['Accept: text/html,*/*', 'Accept-Language: en-US,en;q=0.9'], $headers),
    ]);
    if ($post) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); }
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $result];
}

function getStoryItems($apiData) {
    $items = [];
    if (!$apiData) return $items;
    $processItem = function($it) use (&$items) {
        $url = ''; $thumb = ''; $type = 'image';
        if (!empty($it['video_versions'][0]['url'])) { $url = $it['video_versions'][0]['url']; $type = 'video'; $thumb = $it['image_versions2']['candidates'][0]['url'] ?? ''; }
        elseif (!empty($it['image_versions2']['candidates'][0]['url'])) { $url = $it['image_versions2']['candidates'][0]['url']; $thumb = $url; }
        if ($url) $items[] = ['type' => $type, 'url' => $url, 'thumbnail' => $thumb, 'id' => $it['id'] ?? '', 'taken_at' => $it['taken_at'] ?? 0];
    };
    if (isset($apiData['reels'])) { foreach ($apiData['reels'] as $reel) { if (!empty($reel['items'])) foreach ($reel['items'] as $it) $processItem($it); } }
    elseif (isset($apiData['items'])) { foreach ($apiData['items'] as $it) $processItem($it); }
    elseif (isset($apiData['reel']['items'])) { foreach ($apiData['reel']['items'] as $it) $processItem($it); }
    return $items;
}

$result = ['success' => false, 'message' => 'لم نتمكن من جلب القصص', 'items' => [], 'user' => ['username' => $username, 'full_name' => '', 'profile_pic' => '']];
$headers = ['Accept: */*', 'X-Requested-With: XMLHttpRequest', 'X-IG-App-ID: 936619743392459', 'Referer: https://www.instagram.com/'];

// Method 1: Instagram web profile API
$apiResp = fetchUrl("https://i.instagram.com/api/v1/users/web_profile_info/?username={$username}", $headers);
if ($apiResp['code'] == 200) {
    $apiData = json_decode($apiResp['body'], true);
    if ($apiData && isset($apiData['data']['user'])) {
        $user = $apiData['data']['user'];
        $result['user']['full_name'] = $user['full_name'] ?? '';
        $result['user']['profile_pic'] = $user['profile_pic_url'] ?? $user['profile_pic_url_hd'] ?? '';
        $userId = $user['id'] ?? '';
        if ($userId) {
            $storyResp = fetchUrl("https://i.instagram.com/api/v1/feed/reels_media/?reel_ids={$userId}", $headers);
            if ($storyResp['code'] == 200) {
                $storyData = json_decode($storyResp['body'], true);
                $items = getStoryItems($storyData ?: []);
                if ($items) { $result['success'] = true; $result['message'] = 'تم جلب القصص بنجاح'; $result['items'] = $items; echo json_encode($result, JSON_UNESCAPED_UNICODE); exit; }
            }
        }
    }
}

// Method 2: fastdl.app API
$fastdlBody = json_encode(['username' => $username]);
$fastdlResp = fetchUrl("https://fastdl.app/api/v1/instagram/stories", [
    'Accept: application/json', 'Content-Type: application/json', 'Referer: https://fastdl.app/ar/story-saver', 'Origin: https://fastdl.app'
], true, $fastdlBody);
if ($fastdlResp['code'] == 200) {
    $fastdlData = json_decode($fastdlResp['body'], true);
    if ($fastdlData && isset($fastdlData['success']) && $fastdlData['success'] && isset($fastdlData['data']['items'])) {
        $items = [];
        foreach ($fastdlData['data']['items'] as $it) {
            $url = $it['video_url'] ?? $it['url'] ?? $it['download_url'] ?? '';
            $thumb = $it['thumbnail'] ?? $it['thumb'] ?? '';
            $type = $url && preg_match('/\.mp4/i', $url) ? 'video' : 'image';
            if (!$type && !empty($it['media_type'])) $type = $it['media_type'] == 2 ? 'video' : 'image';
            if ($url) $items[] = ['type' => $type, 'url' => $url, 'thumbnail' => $thumb, 'id' => $it['id'] ?? ''];
        }
        if ($items) {
            $result['success'] = true; $result['message'] = 'تم جلب القصص بنجاح'; $result['items'] = $items;
            if (!empty($fastdlData['data']['user'])) { $result['user']['full_name'] = $fastdlData['data']['user']['full_name'] ?? ''; $result['user']['profile_pic'] = $fastdlData['data']['user']['profile_pic'] ?? ''; }
            echo json_encode($result, JSON_UNESCAPED_UNICODE); exit;
        }
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
