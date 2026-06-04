<?php
require_once __DIR__ . '/config.php';

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
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 420px;
            margin: 100px auto;
            background: #fff;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            text-align: center;
        }
        .login-container h1 {
            color: #1e3c72;
            margin-bottom: 10px;
        }
        .login-container p {
            color: #666;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }
        .login-container input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 15px;
            border: 1px solid #ccd4dd;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            background: #fafbfc;
        }
        .login-container input:focus {
            border-color: #1e3c72;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }
        .login-btn {
            width: 100%;
            background: #1e3c72;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 40px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }
        .login-btn:hover { background: #172d56; }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
    </style>
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