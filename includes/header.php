<?php
// جلوگیری از دسترسی مستقیم
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/config.php';
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>سیستم مدیریت تعمیرگاه جک پارکینگ</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Persian Datepicker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/persian-datepicker/1.2.0/css/persian-datepicker.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- فایل‌های اختصاصی پروژه -->
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css?v=<?= filemtime(BASE_PATH . '/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>reports.css?v=<?= filemtime(BASE_PATH . '/reports.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>invoice.css?v=<?= filemtime(BASE_PATH . '/invoice.css') ?>">
    <script src="<?= BASE_URL ?>script.js?v=<?= filemtime(BASE_PATH . '/script.js') ?>" defer></script>
</head>
<body>
    <div class="container-fluid">
        <header class="main-header">
            <div class="logo">
                <h1>🔧 سیستم مدیریت تعمیرگاه</h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?= BASE_URL ?>index.php">🏠 داشبورد</a></li>
                    <li><a href="<?= BASE_URL ?>repairs/create.php">➕ ثبت سرویس</a></li>
                    <li><a href="<?= BASE_URL ?>repairs/">📋 لیست سرویس‌ها</a></li>
                    <li><a href="<?= BASE_URL ?>customers/list.php">👥 مشتریان</a></li>
                    <li><a href="<?= BASE_URL ?>parts/">⚙️ قطعات</a></li>
                    <li><a href="<?= BASE_URL ?>financial/index.php">💰 امور مالی</a></li>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <li><a href="<?= BASE_URL ?>users.php">👤 مدیریت کاربران</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <main class="main-content">