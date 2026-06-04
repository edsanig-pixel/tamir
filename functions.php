<?php
// ================== تبدیل تاریخ میلادی به شمسی ==================
function gregorian_to_jalali($gy, $gm, $gd, $mod = '') {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    if ($gy > 1600) { $jy = 979; $gy -= 1600; } else { $jy = 0; $gy -= 621; }
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 119) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * ((int)($days / 12053)); $days %= 12053;
    $jy += 4 * ((int)($days / 1461)); $days %= 1461;
    if ($days > 365) { $jy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return ($mod == '') ? [$jy, $jm, $jd] : $jy . $mod . $jm . $mod . $jd;
}

// ================== تبدیل تاریخ شمسی به میلادی ==================
function jalali_to_gregorian($jy, $jm, $jd) {
    $jy = (int)$jy; $jm = (int)$jm; $jd = (int)$jd;
    $jy -= 979; $jm -= 1; $jd -= 1;
    $j_day_no = 365 * $jy + (int)($jy / 33) * 8 + (int)(($jy % 33 + 3) / 4);
    for ($i = 0; $i < $jm; ++$i) $j_day_no += ($i < 6) ? 31 : 30;
    $j_day_no += $jd;
    $g_day_no = $j_day_no + 79;
    $gy = 1600 + 400 * (int)($g_day_no / 146097);
    $g_day_no %= 146097;
    $leap = true;
    if ($g_day_no >= 36525) { $g_day_no--; $gy += 100 * (int)($g_day_no / 36524); $g_day_no %= 36524; if ($g_day_no >= 365) $g_day_no++; else $leap = false; }
    $gy += 4 * (int)($g_day_no / 1461); $g_day_no %= 1461;
    if ($g_day_no >= 366) { $leap = false; $g_day_no--; $gy += (int)($g_day_no / 365); $g_day_no %= 365; }
    $days_in_month = [0, 31, ($leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $i = 0;
    while ($g_day_no >= $days_in_month[$i]) { $g_day_no -= $days_in_month[$i]; $i++; }
    $gm = $i; $gd = $g_day_no + 1;
    return [$gy, $gm, $gd];
}

// ================== دریافت تاریخ شمسی از timestamp ==================
function get_jalali_date_str($timestamp) {
    $date = getdate($timestamp);
    return gregorian_to_jalali($date['year'], $date['mon'], $date['mday'], '/');
}

// ================== فرمت ریال ==================
function format_rial($number) {
    return number_format($number, 0, '.', ',') . ' ریال';
}

// --- تولید شماره فاکتور (جدید) ---
function generate_invoice_number($pdo) {
    // دریافت سال شمسی جاری
    $now = time();
    $jalali = gregorian_to_jalali(date('Y', $now), date('m', $now), date('d', $now));
    $year = $jalali[0];
    
    // آخرین شماره فاکتور برای همین سال
    $stmt = $pdo->prepare("SELECT MAX(invoice_number) FROM repairs WHERE invoice_number LIKE ?");
    $stmt->execute(["FA-$year-%"]);
    $last = $stmt->fetchColumn();
    
    if ($last) {
        $num = (int)substr($last, strrpos($last, '-') + 1) + 1;
    } else {
        $num = 1;
    }
    return "FA-$year-" . str_pad($num, 4, '0', STR_PAD_LEFT);
}

function jalali_is_leap_year($jy) {
    $a = ($jy - 474) % 2820 + 474;
    return ((($a + 38) * 31) % 128) < 31;
}

function validate_jalali_date($date) {
    if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $matches)) {
        return false;
    }
    $jy = (int)$matches[1];
    $jm = (int)$matches[2];
    $jd = (int)$matches[3];

    if ($jm < 1 || $jm > 12 || $jd < 1) {
        return false;
    }
    if ($jm <= 6) {
        $maxDay = 31;
    } elseif ($jm <= 11) {
        $maxDay = 30;
    } else {
        $maxDay = jalali_is_leap_year($jy) ? 30 : 29;
    }
    return $jd <= $maxDay;
}

function convert_jalali_to_gregorian_date($date) {
    if (!validate_jalali_date($date)) {
        return null;
    }
    [$jy,$jm,$jd] = explode('/', $date);
    $g = jalali_to_gregorian((int)$jy, (int)$jm, (int)$jd);
    return sprintf('%04d-%02d-%02d', $g[0], $g[1], $g[2]);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// بررسی لاگین (برای صفحات محافظت‌شده)
function require_login($pdo) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
        session_unset();
        session_destroy();
        header("Location: login.php?expired=1");
        exit;
    }

    $_SESSION['last_activity'] = time();
    
    if (!isset($_SESSION['user_full_name'])) {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
        } else {
            session_destroy();
            header("Location: login.php");
            exit;
        }
    }
}

// ثبت لاگ فعالیت
function log_activity($pdo, $user_id, $action, $entity_type = null, $entity_id = null, $description = null) {
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $entity_type, $entity_id, $description]);
}

?>

