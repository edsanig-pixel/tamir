<?php
require_once __DIR__ . '/includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'نام کاربری و رمز عبور را وارد کنید.';
    } else {
        // جستجوی کاربر بدون شرط is_active
        $stmt = $pdo->prepare("SELECT id, username, password, full_name, role, is_active FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // کاربر پیدا شد
            if (!$user['is_active']) {
                $error = '⛔ اکانت شما غیر فعال شده است. با مدیر سیستم تماس بگیرید.';
            } elseif (password_verify($password, $user['password'])) {
                // ورود موفق
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];

                log_activity($pdo, $user['id'], 'login', 'user', $user['id'], 'ورود به سیستم');

                header("Location: index.php");
                exit;
            } else {
                // رمز اشتباه
                $error = '❌ رمز عبور اشتباه است.';
            }
        } else {
            // کاربر وجود ندارد
            $error = '❌ نام کاربری یا رمز عبور اشتباه است.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="login-container">
    <h1>سیستم مدیریت سرویس</h1>
    <p>لطفاً وارد حساب کاربری خود شوید</p>
    
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="نام کاربری" required autofocus>
        <input type="password" name="password" placeholder="رمز عبور" required>
        <button type="submit" class="login-btn">ورود به سیستم</button>
    </form>
</div>
</body>
</html>