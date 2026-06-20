<?php
require_once __DIR__ . '/includes/config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username === ADMIN_USER && $password === ADMIN_PASS) {
        $_SESSION['admin_logged'] = true;
        header('Location: dashboard.php');
        exit;
    }
    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
}

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?= SITE_NAME ?> Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/fonts-cairo.css">
    <style>
        body { background: #0F172A; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { background: #1E293B; border-radius: 20px; padding: 40px; width: 100%; max-width: 400px; margin: 1rem; }
        .login-logo { text-align: center; font-size: 1.5rem; font-weight: 900; color: #fff; margin-bottom: 8px; }
        .login-logo span { color: #6366F1; }
        .login-sub { text-align: center; color: #64748b; font-size: 0.85rem; margin-bottom: 32px; }
        .login-box .form-group label { color: #94a3b8; }
        .login-box .form-control { background: #0F172A; border-color: #334155; color: #fff; }
        .login-box .form-control:focus { border-color: #6366F1; }
        .login-box .btn { width: 100%; justify-content: center; padding: 12px; font-size: 0.95rem; }
        .login-error { background: rgba(239,68,68,0.1); color: #fca5a5; padding: 10px 16px; border-radius: 10px; font-size: 0.85rem; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-logo"><?= SITE_NAME ?> <span>Admin</span></div>
        <div class="login-sub">يرجى تسجيل الدخول للوصول إلى لوحة التحكم</div>
        <?php if (isset($error)): ?>
            <div class="login-error"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input class="form-control" type="text" name="username" required placeholder="admin">
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input class="form-control" type="password" name="password" required placeholder="••••••••">
            </div>
            <button class="btn btn-primary" type="submit">تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
