<?php
require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    log_activity($pdo, $_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'خروج از سیستم');
}

session_destroy();
header("Location: login.php");
exit;