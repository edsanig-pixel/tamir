<?php
require_once __DIR__ . '../includes/config.php';
require_login($pdo);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

// دریافت اطلاعات قبل از حذف
$stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
$stmt->execute([$id]);
$oldData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($oldData) {
    // ذخیره کل رکورد به‌صورت JSON در لاگ
    log_activity($pdo, $_SESSION['user_id'], 'delete', 'repair', $id, json_encode($oldData, JSON_UNESCAPED_UNICODE));

    // حذف قطعات مرتبط
    $pdo->prepare("DELETE FROM repair_parts WHERE repair_id = ?")->execute([$id]);

    // حذف خود رکورد
    $stmt = $pdo->prepare("DELETE FROM repairs WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?msg=deleted");
exit;