<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - لوحة التحكم</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/fonts-cairo.css">
</head>
<body>
<header class="admin-header">
    <div class="admin-container">
        <div class="admin-header-inner">
            <a href="dashboard.php" class="admin-logo"><?= SITE_NAME ?> <span>Admin</span></a>
            <nav class="admin-nav">
                <a href="dashboard.php">لوحة التحكم</a>
                <a href="categories.php">التصنيفات</a>
                <a href="tools.php">الأدوات</a>
                <a href="add_tool.php" class="nav-add">إضافة أداة</a>
                <a href="index.php?logout=1" class="nav-logout">تسجيل خروج</a>
            </nav>
        </div>
    </div>
</header>
<main class="admin-main">
    <div class="admin-container">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash flash-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                <?= $_SESSION['flash'] ?>
                <button onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
        <?php endif; ?>
