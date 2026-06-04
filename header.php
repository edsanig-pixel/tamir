<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

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
    <link rel="stylesheet" href="style.css">
    <style>
        /* ========== منوی آبشاری ========== */
        .main-nav { display: flex; align-items: center; gap: 5px; }
        .menu-item { position: relative; display: inline-block; }
        .menu-link {
            display: flex; align-items: center; gap: 6px;
            padding: 10px 20px; border-radius: 40px;
            background: #f8fafc; color: #1e3c72;
            text-decoration: none; font-weight: 600; transition: 0.2s;
            border: 1px solid #dce1e8; white-space: nowrap;
        }
        .menu-link:hover, .menu-link.active {
            background: #1e3c72; color: white; border-color: #1e3c72;
        }
        .menu-link .arrow { font-size: 10px; margin-right: 4px; transition: 0.2s; }
        .menu-item:hover .arrow { transform: rotate(180deg); }

        .submenu {
            display: none; position: absolute; top: 100%; right: 0;
            background: white; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            min-width: 220px; z-index: 1000; margin-top: 8px; border: 1px solid #e0e7ef;
            padding: 8px 0;
        }
        .menu-item:hover .submenu { display: block; animation: fadeSlide 0.2s ease; }
        .submenu a {
            display: flex; align-items: center; gap: 8px; padding: 12px 20px;
            text-decoration: none; color: #334155; transition: 0.2s;
        }
        .submenu a:hover { background: #f4f6f9; color: #1e3c72; }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========== منوی کاربری ========== */
        .user-menu { position: relative; display: inline-block; }
        .user-menu-btn {
            background: #1e3c72; color: white; padding: 10px 20px; border-radius: 40px;
            cursor: pointer; border: none; font-family: inherit; font-weight: bold;
            display: flex; align-items: center; gap: 8px; transition: 0.2s;
        }
        .user-menu-btn:hover { background: #172d56; }
        .user-dropdown {
            display: none; position: absolute; top: 100%; left: 0; background: white;
            border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); min-width: 220px;
            z-index: 1000; margin-top: 8px; border: 1px solid #e0e7ef;
        }
        .user-dropdown.show { display: block; }
        .user-dropdown a {
            display: block; padding: 12px 20px; text-decoration: none; color: #333; transition: 0.2s;
        }
        .user-dropdown a:hover { background: #f4f6f9; }
    </style>
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
                        <a href="<?= $item['url'] ?>" class="menu-link <?= $isActive ? 'active' : '' ?>">
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
                        <a href="users.php">👥 مدیریت کاربران</a>
                    <?php endif; ?>
                    <a href="logs.php">📋 لاگ سیستم</a>
                    <div class="divider" style="border-top:1px solid #eee; margin:4px 0;"></div>
                    <a href="logout.php" style="color:#e74c3c;">🚪 خروج</a>
                </div>
            </div>
            <script>
                document.addEventListener('click', function(e) {
                    const menu = document.getElementById('userDropdown');
                    const btn = e.target.closest('.user-menu-btn');
                    if (!btn && menu && !menu.contains(e.target)) menu.classList.remove('show');
                });
				
				document.querySelectorAll('.menu-item').forEach(item => {
    let timer;
    item.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        item.querySelector('.submenu') && (item.querySelector('.submenu').style.display = 'block');
    });
    item.addEventListener('mouseleave', () => {
        const sub = item.querySelector('.submenu');
        timer = setTimeout(() => { if (sub) sub.style.display = 'none'; }, 150); // ۱۵۰ میلی‌ثانیه تأخیر
    });
});
            </script>
        <?php else: ?>
            <a href="login.php" class="btn small">ورود</a>
        <?php endif; ?>
    </header>
    <main>