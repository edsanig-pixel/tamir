<?php
// config.php - تنظیمات اصلی سیستم
session_start();

// --- تنظیمات پایگاه داده ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'repair_shop');  // اسم دیتابیس خود را بررسی کنید
define('DB_USER', 'root');
define('DB_PASS', '123456');

// --- تنظیمات مسیرها ---
define('BASE_PATH', dirname(__DIR__));  // مسیر ریشه پروژه
define('BASE_URL', 'http://localhost/tamir/');  // آدرس پروژه در مرورگر

// --- اتصال به دیتابیس ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// --- بارگذاری توابع کمکی ---
require_once __DIR__ . '/../functions.php';
?>