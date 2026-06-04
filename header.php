<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// دریافت آیتم‌های سطح بالا
$topMenu = $pdo->query("
    SELECT * FROM menu_items 
    WHERE parent_id IS NULL AND is_active = 1 
    ORDER BY sort_order
")->fetchAll(PDO::FETCH_ASSOC);

// دریافت تمام زیرمنوها
$allSubMenus = $pdo->query("
    SELECT * FROM menu_items 
    WHERE parent_id IS NOT NULL AND is_active = 1 
    ORDER BY sort_order
")->fetchAll(PDO::FETCH_ASSOC);

// گروه‌بندی زیرمنوها بر اساس parent_id
$subMenus = [];
foreach ($allSubMenus as $sub) {
    $subMenus[$sub['parent_id']][] = $sub;
}

function resolveMenuUrl($url) {
    $url = trim($url);
    $mapping = [
        'customers.php' => 'customers',
        'parts.php' => 'parts',
        'suppliers.php' => 'suppliers',
        'purchases.php' => 'purchases',
        'users.php' => 'users',
        'logs.php' => 'logs',
        'profile.php' => 'profile',
        'reports.php' => 'financial',
        'financial_report.php' => 'financial',
        'create.php' => 'repairs/create',
        'edit.php' => 'repairs/edit',
        'invoice.php' => 'repairs/invoice',
        'customer_profile.php' => 'customers/profile',
        'customer_print.php' => 'customers/print',
    ];
    if (isset($mapping[$url])) {
        return BASE_URL . '/' . $mapping[$url];
    }
    if (preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $url)) {
        return BASE_URL . '/' . pathinfo($url, PATHINFO_FILENAME);
    }
    return $url;
}

$isLoggedIn = isset($_SESSION['user_id']);
$userFullName = $_SESSION['user_full_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';
$username = $_SESSION['username'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم مدیریت سرویس جک پارکینگ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
    <script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/script.js" defer></script>
</head>
<body>
<div class="container">
    <header class="main-header">
        <div class="logo"><h1>🚗 سرویس جک پارکینگ</h1></div>
        <nav class="main-nav">
            <?php foreach ($topMenu as $item): 
                $hasSub = isset($subMenus[$item['id']]) && count($subMenus[$item['id']]) > 0;
                $isActive = ($currentPage == basename($item['url']));
            ?>
                <div class="menu-item">
                    <?php if ($hasSub): ?>
                        <a href="javascript:void(0)" class="menu-link <?= $isActive ? 'active' : '' ?>">
                            <?= htmlspecialchars($item['title']) ?>
                            <span class="arrow">▼</span>
                        </a>
                        <div class="submenu">
                            <?php foreach ($subMenus[$item['id']] as $sub): ?>
                                <a href="<?= $sub['url'] ?>"><?= htmlspecialchars($sub['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <a href="<?= resolveMenuUrl($item['url']) ?>" class="menu-link <?= $isActive ? 'active' : '' ?>">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <?php if ($isLoggedIn): ?>
            <div class="user-menu">
                <button class="user-menu-btn" onclick="document.getElementById('userDropdown').classList.toggle('show')">
                    👤 <?= htmlspecialchars($userFullName) ?> ▼
                </button>
                <div id="userDropdown" class="user-dropdown">
                    <a href="#" onclick="openProfileModal(); return false;">👤 ویرایش پروفایل</a>
                    <?php if ($userRole === 'super_admin'): ?>
                        <a href="<?= BASE_URL ?>/users">👥 مدیریت کاربران</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/logs">📋 لاگ سیستم</a>
                    <div class="divider" style="border-top:1px solid #eee; margin:4px 0;"></div>
                    <a href="<?= BASE_URL ?>/logout.php" style="color:#e74c3c;">🚪 خروج</a>
                </div>
            </div>
            
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn small">ورود</a>
        <?php endif; ?>
    </header>
    <main>