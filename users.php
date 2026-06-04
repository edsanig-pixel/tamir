<?php
require_once __DIR__ . '/config.php';
require_login($pdo);

// فقط super_admin می‌تواند کاربران را مدیریت کند
if ($_SESSION['user_role'] !== 'super_admin') {
    header("Location: index.php");
    exit;
}

$message = '';

// افزودن کاربر جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'admin';

    if ($username === '' || $password === '' || $full_name === '') {
        $message = '<p class="error">همه فیلدها الزامی هستند.</p>';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $message = '<p class="error">این نام کاربری قبلاً ثبت شده است.</p>';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed, $full_name, $role]);
            log_activity($pdo, $_SESSION['user_id'], 'create', 'user', $pdo->lastInsertId(), "ایجاد کاربر $username");
            $message = '<p class="success">✅ کاربر جدید با موفقیت ایجاد شد.</p>';
        }
    }
}

// غیرفعال/فعال کردن کاربر
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND id != ?");
    $stmt->execute([$_GET['toggle'], $_SESSION['user_id']]);
    log_activity($pdo, $_SESSION['user_id'], 'update', 'user', $_GET['toggle'], 'تغییر وضعیت کاربر');
    header("Location: users.php");
    exit;
}

// دریافت لیست کاربران
$users = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<h2>👥 مدیریت کاربران</h2>
<div class="card">
    <?= $message ?>
    
    <h3>➕ افزودن کاربر جدید</h3>
    <form method="POST">
        <input type="hidden" name="add_user" value="1">
        <div class="form-row">
            <input type="text" name="username" placeholder="نام کاربری" required>
            <input type="password" name="password" placeholder="رمز عبور" required>
            <input type="text" name="full_name" placeholder="نام کامل" required>
            <select name="role">
                <option value="admin">ادمین</option>
                <option value="super_admin">سوپر ادمین</option>
            </select>
            <button type="submit" class="btn primary">ایجاد کاربر</button>
        </div>
    </form>

    <hr>
    <h3>📋 لیست کاربران</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>نام کاربری</th>
                <th>نام کامل</th>
                <th>نقش</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= $u['role'] === 'super_admin' ? 'سوپر ادمین' : 'ادمین' ?></td>
                <td><?= $u['is_active'] ? '✅ فعال' : '❌ غیرفعال' ?></td>
                <td>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <a href="?toggle=<?= $u['id'] ?>" class="btn small <?= $u['is_active'] ? 'danger' : 'success' ?>">
                            <?= $u['is_active'] ? 'غیرفعال کردن' : 'فعال کردن' ?>
                        </a>
                    <?php else: ?>
                        <span style="color:#666;">خودتان هستید</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</main>
</body>
</html>