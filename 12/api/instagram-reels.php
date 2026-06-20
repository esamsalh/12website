<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
if (!$url) { die(json_encode(['success' => false, 'message' => 'الرجاء إدخال رابط ريلز انستقرام', 'items' => []])); }

$shortcode = '';
if (preg_match('#instagram\.com/(?:reel|reels|p)/([a-zA-Z0-9_-]+)#', $url, $m)) $shortcode = $m[1];
if (!$shortcode) { die(json_encode(['success' => false, 'message' => 'رابط ريلز انستقرام غير صالح', 'items' => []])); }

function fetchUrl($url, $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_COOKIE => 'ig_did=AE0A2D8E-2F9C-4B97-8A1E-3C5D6E7F8A9B; mid=Z_3X4gALAAF3FJh6p3F6G3F7H8I9',
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => array_merge(['Accept: text/html,*/*', 'Accept-Language: en-US,en;q=0.9'], $headers),
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $result];
}

$result = ['success' => false, 'message' => 'لم نتمكن من جلب الفيديو', 'items' => [], 'user' => ['username' => '', 'full_name' => '', 'profile_pic' => '']];

// Try oEmbed + internal API
$oembed = fetchUrl("https://api.instagram.com/oembed?url=https://www.instagram.com/reel/{$shortcode}/", ['Accept: application/json']);
if ($oembed['code'] == 200) {
    $oData = json_decode($oembed['body'], true);
    if ($oData && !empty($oData['thumbnail_url'])) {
        $result['user']['full_name'] = $oData['author_name'] ?? '';
        $result['user']['username'] = $oData['author_name'] ?? '';
        $result['user']['profile_pic'] = $oData['author_url'] ?? '';

        $apiResp = fetchUrl("https://i.instagram.com/api/v1/media/{$shortcode}/info/", [
            'Accept: */*', 'X-Requested-With: XMLHttpRequest', 'X-IG-App-ID: 936619743392459', 'Referer: https://www.instagram.com/'
        ]);
        if ($apiResp['code'] == 200) {
            $apiData = json_decode($apiResp['body'], true);
            if ($apiData && isset($apiData['items'][0])) {
                $media = $apiData['items'][0];
                if (!empty($media['user']['username'])) {
                    $result['user']['username'] = $media['user']['username'];
                    $result['user']['full_name'] = $media['user']['full_name'] ?? $media['user']['username'];
                    $result['user']['profile_pic'] = $media['user']['profile_pic_url'] ?? '';
                }
                if (!empty($media['video_versions'])) {
                    $bestUrl = ''; $bestWidth = 0;
                    foreach ($media['video_versions'] as $vv) {
                        if (($vv['width'] ?? 0) > $bestWidth) { $bestWidth = $vv['width']; $bestUrl = $vv['url'] ?? ''; }
                    }
                    if ($bestUrl) {
                        $result['items'][] = ['type' => 'video', 'url' => $bestUrl, 'thumbnail' => $media['image_versions2']['candidates'][0]['url'] ?? $oData['thumbnail_url'] ?? '', 'quality' => $bestWidth >= 1080 ? 'HD' : 'SD', 'width' => $bestWidth];
                        $result['success'] = true; $result['message'] = 'تم جلب الفيديو بنجاح';
                        echo json_encode($result, JSON_UNESCAPED_UNICODE); exit;
                    }
                }
            }
        }
    }
}

// Try scraping
$pageResp = fetchUrl("https://www.instagram.com/reel/{$shortcode}/", [
    'X-Requested-With: XMLHttpRequest', 'X-IG-App-ID: 936619743392459', 'Referer: https://www.instagram.com/'
]);
$html = $pageResp['body'] ?? '';
if ($pageResp['code'] == 200 && $html) {
    if (preg_match('/window\.__INITIAL_STATE__\s*=\s*({.+?});\s*</s', $html, $m)) {
        $data = json_decode($m[1], true);
        if ($data && isset($data['shortcode_media'])) {
            $sm = $data['shortcode_media'];
            if (!empty($sm['owner'])) { $result['user']['username'] = $sm['owner']['username'] ?? ''; $result['user']['full_name'] = $sm['owner']['full_name'] ?? ''; $result['user']['profile_pic'] = $sm['owner']['profile_pic_url'] ?? ''; }
            if (!empty($sm['video_url'])) $result['items'][] = ['type' => 'video', 'url' => $sm['video_url'], 'thumbnail' => $sm['display_url'] ?? '', 'quality' => 'HD', 'width' => $sm['dimensions']['width'] ?? 0];
            elseif (!empty($sm['display_url'])) $result['items'][] = ['type' => 'image', 'url' => $sm['display_url'], 'thumbnail' => $sm['display_url'], 'quality' => 'HD', 'width' => $sm['dimensions']['width'] ?? 0];
            if (!empty($result['items'])) { $result['success'] = true; $result['message'] = 'تم جلب المحتوى بنجاح'; echo json_encode($result, JSON_UNESCAPED_UNICODE); exit; }
        }
    }
    if (preg_match('/<script[^>]*id="__NEXT_DATA__"[^>]*>({.+?})<\/script>/s', $html, $m)) {
        $data = json_decode($m[1], true);
        if ($data && isset($data['props']['pageProps']['media'])) {
            $media = $data['props']['pageProps']['media'];
            if (!empty($media['owner'])) { $result['user']['username'] = $media['owner']['username'] ?? ''; $result['user']['full_name'] = $media['owner']['full_name'] ?? ''; $result['user']['profile_pic'] = $media['owner']['profile_pic_url'] ?? ''; }
            if (!empty($media['video_url'])) $result['items'][] = ['type' => 'video', 'url' => $media['video_url'], 'thumbnail' => $media['display_url'] ?? '', 'quality' => 'HD', 'width' => $media['dimensions']['width'] ?? 0];
            if (!empty($result['items'])) { $result['success'] = true; $result['message'] = 'تم جلب المحتوى بنجاح'; echo json_encode($result, JSON_UNESCAPED_UNICODE); exit; }
        }
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
