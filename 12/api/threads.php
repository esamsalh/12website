<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$postUrl = isset($_GET['url']) ? trim($_GET['url']) : '';
$m = [];
if (preg_match('/(https?:\/\/(?:www\.)?threads\.(?:net|com)\/[^\s"\'<>]+)/i', $postUrl, $m)) $postUrl = $m[1];
$postUrl = explode('?', $postUrl)[0];

if (!preg_match('/^https?:\/\/(www\.)?threads\.(net|com)\//i', $postUrl)) {
    echo json_encode(['error' => 'invalid_url', 'media' => []]);
    exit;
}

$apiUrl = preg_replace('/threads\.com\//i', 'threads.net/', $postUrl);

$ch = curl_init('https://lovethreads.net/api/ajaxSearch');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['q' => $apiUrl, 't' => 'media', 'lang' => 'en']),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'Origin: https://lovethreads.net',
        'Referer: https://lovethreads.net/en',
        'X-Requested-With: XMLHttpRequest',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]
]);

$ltData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 && $ltData) {
    $ltResult = json_decode($ltData, true);
    if ($ltResult && !empty($ltResult['status']) && $ltResult['status'] === 'ok' && !empty($ltResult['data'])) {
        $html = $ltResult['data'];
        $media = [];
        $seen = [];

        $thumbUrl = '';
        if (preg_match('/<img[^>]+src="([^"]+)"[^>]*alt="LoveThreads"/i', $html, $tm)) $thumbUrl = $tm[1];

        // Match both href-before-title and title-before-href
        preg_match_all('/<a[^>]*\bhref="([^"]+)"[^>]*\btitle="Download (Video|Thumbnail)"[^>]*>/i', $html, $m1, PREG_SET_ORDER);
        preg_match_all('/<a[^>]*\btitle="Download (Video|Thumbnail)"[^>]*\bhref="([^"]+)"[^>]*>/i', $html, $m2, PREG_SET_ORDER);

        foreach (array_merge($m1, $m2) as $match) {
            $type = ($match[1] === 'Video' || $match[2] === 'Video') ? 'video' : 'image';
            $url = $match[1] === 'Video' || $match[1] === 'Thumbnail' ? $match[2] : $match[1];
            if (in_array($url, $seen)) continue;
            $seen[] = $url;
            $media[] = ['type' => $type, 'url' => $url, 'thumbnail' => $type === 'video' ? $thumbUrl : $url];
        }

        if (empty($media)) {
            preg_match_all('/<option value="([^"]+)">(\d+x\d+)<\/option>/i', $html, $optM, PREG_SET_ORDER);
            if ($optM) {
                $best = $optM[0];
                foreach ($optM as $o) {
                    $best = (array_product(explode('x', $o[2])) > array_product(explode('x', $best[2]))) ? $o : $best;
                }
                $media[] = ['type' => 'image', 'url' => $best[1], 'thumbnail' => $best[1]];
            } elseif ($thumbUrl) {
                $media[] = ['type' => 'image', 'url' => $thumbUrl, 'thumbnail' => $thumbUrl];
            }
        }

        if ($media) {
            $hasVideo = count(array_filter($media, fn($m) => $m['type'] === 'video')) > 0;
            $result = $hasVideo ? array_values(array_filter($media, fn($m) => $m['type'] === 'video')) : $media;
            echo json_encode(['media' => $result]);
            exit;
        }
    }
}

// Fallback: scrape
$pageResp = @file_get_contents($postUrl, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]]));
if ($pageResp) {
    $media = []; $seen = [];
    $ogVid = ''; $ogImg = '';
    if (preg_match('/<meta[^>]+property="og:video"[^>]+content="([^"]+)"/i', $pageResp, $m)) $ogVid = $m[1];
    if (preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $pageResp, $m)) $ogImg = $m[1];
    if ($ogVid) $media[] = ['type' => 'video', 'url' => $ogVid, 'thumbnail' => $ogImg ?: ''];
    if (empty($media) && preg_match_all('/https?:\/\/[^"\'<>]+?\.mp4[^"\'\s<>]*/i', $pageResp, $m)) {
        foreach ($m[0] as $url) {
            if (in_array($url, $seen)) continue;
            $seen[] = $url;
            $media[] = ['type' => 'video', 'url' => $url, 'thumbnail' => $ogImg ?: ''];
        }
    }
    if ($media) {
        $hasVideo = count(array_filter($media, fn($m) => $m['type'] === 'video')) > 0;
        echo json_encode(['media' => $hasVideo ? array_values(array_filter($media, fn($m) => $m['type'] === 'video')) : $media]);
        exit;
    }
}

echo json_encode(['error' => 'no_media', 'media' => []]);
