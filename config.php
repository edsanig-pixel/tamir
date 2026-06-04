<?php
$host = 'localhost';
$dbname = 'repair_shop';
$username = 'root';       // نام کاربری MySQL شما
$password = '123456';           // رمز عبور (معمولاً خالی در XAMPP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ خطا در اتصال به دیتابیس: " . $e->getMessage());
}

define('BASE_URL', '/tamir');

require_once __DIR__ . '/functions.php';
?>