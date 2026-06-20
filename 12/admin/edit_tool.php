<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$toolId = $_GET['id'] ?? '';
if (!$toolId) {
    $_SESSION['flash'] = 'لم يتم تحديد الأداة';
    $_SESSION['flash_type'] = 'error';
    header('Location: tools.php');
    exit;
}

$tools = getTools();
$found = null;
foreach ($tools as $t) {
    if ($t['id'] === $toolId) { $found = $t; break; }
}

if (!$found) {
    $_SESSION['flash'] = 'الأداة غير موجودة';
    $_SESSION['flash_type'] = 'error';
    header('Location: tools.php');
    exit;
}

$_SESSION['edit_tool_data'] = $found;
header('Location: add_tool.php?edit=1');
exit;
