<?php
require_once __DIR__ . '../includes/config.php';
require_login($pdo);

$message = '';
$user_id = $_SESSION['user_id'];

// دریافت اطلاعات کاربر
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '') {
        $message = '<p class="error">نام کامل الزامی است.</p>';
    } else {
        try {
            // اگر رمز جدید وارد شده باشد
            if ($new_password !== '') {
                if (!password_verify($current_password, $user['password'])) {
                    $message = '<p class="error">رمز عبور فعلی اشتباه است.</p>';
                } elseif ($new_password !== $confirm_password) {
                    $message = '<p class="error">رمز عبور جدید با تکرار آن مطابقت ندارد.</p>';
                } elseif (strlen($new_password) < 6) {
                    $message = '<p class="error">رمز عبور جدید باید حداقل ۶ کاراکتر باشد.</p>';
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email = :email, password = :password WHERE id = :id");
                    $stmt->bindValue(':full_name', $full_name, PDO::PARAM_STR);
                    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $stmt->bindValue(':password', $hashed, PDO::PARAM_STR);
                    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    log_activity($pdo, $user_id, 'update', 'user', $user_id, 'ویرایش پروفایل و تغییر رمز عبور');
                    $message = '<p class="success">✅ اطلاعات و رمز عبور با موفقیت به‌روز شد.</p>';
                }
            } else {
                // فقط نام و ایمیل
                $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email = :email WHERE id = :id");
                $stmt->bindValue(':full_name', $full_name, PDO::PARAM_STR);
                $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                log_activity($pdo, $user_id, 'update', 'user', $user_id, 'ویرایش پروفایل');
                $message = '<p class="success">✅ اطلاعات با موفقیت به‌روز شد.</p>';
            }

            // بازخوانی اطلاعات
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

        } catch (Exception $e) {
            $message = '<p class="error">❌ خطا: ' . $e->getMessage() . '</p>';
        }
    }
}

require_once __DIR__ . '../includes/header.php';
?>

<!-- HTML همانند قبل (بدون تغییر) -->
<h2>👤 پروفایل کاربری</h2>
<div class="card">
    <?= $message ?>
    <form method="POST">
        <fieldset>
            <legend>اطلاعات حساب</legend>
            <div class="form-row">
                <label>نام کاربری:</label>
                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>
            <div class="form-row">
                <label>نام کامل:</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            <div class="form-row">
                <label>ایمیل (اختیاری):</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
        </fieldset>

        <fieldset>
            <legend>تغییر رمز عبور</legend>
            <div class="form-row">
                <label>رمز عبور فعلی:</label>
                <input type="password" name="current_password">
            </div>
            <div class="form-row">
                <label>رمز عبور جدید:</label>
                <input type="password" name="new_password">
            </div>
            <div class="form-row">
                <label>تکرار رمز عبور جدید:</label>
                <input type="password" name="confirm_password">
            </div>
            <small style="color:#666;">در صورت عدم نیاز به تغییر رمز، این فیلدها را خالی بگذارید.</small>
        </fieldset>

        <div class="form-actions" style="text-align:center; margin-top:20px;">
            <button type="submit" class="btn primary">💾 ذخیره تغییرات</button>
        </div>
    </form>
</div>
</main>
</body>
</html>