<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$dataFile = __DIR__ . '/short-links.json';

if (isset($_GET['go'])) {
    $code = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['go']);
    if ($code && file_exists($dataFile)) {
        $links = json_decode(file_get_contents($dataFile), true) ?: [];
        if (isset($links[$code])) {
            header('Location: ' . $links[$code]);
            exit;
        }
    }
    header('HTTP/1.0 404 Not Found');
    echo json_encode(['error' => 'Not found']);
    exit;
}

if (isset($_GET['save'])) {
    $url = $_GET['save'];
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['error' => 'Invalid URL']);
        exit;
    }
    $links = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];
    $code = substr(md5($url . time()), 0, 6);
    $links[$code] = $url;
    file_put_contents($dataFile, json_encode($links, JSON_PRETTY_PRINT), LOCK_EX);
    echo json_encode(['success' => true, 'code' => $code]);
    exit;
}

echo json_encode(['error' => 'invalid_request']);
