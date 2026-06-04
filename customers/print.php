<?php
require_once __DIR__ . '/../config.php';
require_login($pdo);

$customerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$customerId || $customerId < 1) {
    die('شناسه مشتری نامعتبر است.');
}
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) { die('مشتری یافت نشد.'); }

$historyStmt = $pdo->prepare("
    SELECT r.*, st.name AS service_type_name, dt.name AS device_type_name,
           (SELECT COALESCE(SUM(quantity * unit_price),0) FROM repair_parts WHERE repair_id = r.id) AS parts_total
    FROM repairs r
    LEFT JOIN service_types st ON r.service_type_id = st.id
    LEFT JOIN device_types dt ON r.device_type_id = dt.id
    WHERE r.customer_id = ?
    ORDER BY r.id DESC
");
$historyStmt->execute([$customerId]);
$repairs = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$totalLabor = array_sum(array_column($repairs, 'labor_cost'));
$totalParts = array_sum(array_column($repairs, 'parts_total'));
$totalExtra = array_sum(array_column($repairs, 'extra_costs'));
$grandTotal = $totalLabor + $totalParts + $totalExtra;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارش مشتری - <?= htmlspecialchars($customer['name']) ?></title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('fonts/Vazir-Regular.ttf') format('truetype');
            font-weight: normal;
        }
        @font-face {
            font-family: 'Vazir';
            src: url('fonts/Vazir-Bold.ttf') format('truetype');
            font-weight: bold;
        }
        body {
            font-family: 'Vazir', Tahoma, sans-serif; direction: rtl;
            background: #fff; margin: 0; padding: 20px;
        }
        .print-container { max-width: 1000px; margin: auto; }
        .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #1e3a8a; margin: 0; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 8px 10px; border: 1px solid #ccc; }
        .info-table .label { background: #f1f5f9; font-weight: bold; width: 30%; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: right; }
        th { background: #1e3a8a; color: #fff; }
        .total-row { font-weight: bold; background: #f1f5f9; }
        .no-print { text-align: center; margin-top: 20px; }
        .btn { background: #1e3a8a; color: #fff; padding: 10px 25px; border-radius: 30px; border: none; cursor: pointer; font-family: inherit; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="print-container">
    <div class="header">
        <h1>پرونده مشتری</h1>
        <span><?= get_jalali_date_str(time()) ?></span>
    </div>
    <table class="info-table">
        <tr><td class="label">نام</td><td><?= htmlspecialchars($customer['name']) ?></td></tr>
        <tr><td class="label">تلفن اصلی</td><td><?= htmlspecialchars($customer['phone']) ?></td></tr>
        <?php if (!empty($customer['phone2'])): ?><tr><td class="label">تلفن ثانویه</td><td><?= htmlspecialchars($customer['phone2']) ?></td></tr><?php endif; ?>
        <tr><td class="label">نوع</td><td><?= ($customer['customer_type'] ?? 'personal') === 'company' ? 'شرکتی' : 'شخصی' ?></td></tr>
        <?php if (!empty($customer['company_name'])): ?><tr><td class="label">شرکت</td><td><?= htmlspecialchars($customer['company_name']) ?></td></tr><?php endif; ?>
        <?php if (!empty($customer['address'])): ?><tr><td class="label">آدرس</td><td><?= htmlspecialchars($customer['address']) ?></td></tr><?php endif; ?>
        <?php if (!empty($customer['email'])): ?><tr><td class="label">ایمیل</td><td><?= htmlspecialchars($customer['email']) ?></td></tr><?php endif; ?>
        <tr><td class="label">تعداد کل سرویس‌ها</td><td><?= count($repairs) ?></td></tr>
    </table>

    <?php if (count($repairs) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>فاکتور</th><th>تاریخ</th><th>دستگاه</th><th>نوع خدمت</th><th>مزد</th><th>قطعات</th><th>جمع</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($repairs as $i => $r): $rowTotal = $r['labor_cost'] + ($r['parts_total'] ?? 0) + $r['extra_costs']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($r['invoice_number']) ?></td>
                        <td><?= $r['service_date'] ? get_jalali_date_str(strtotime($r['service_date'])) : '—' ?></td>
                        <td><?= htmlspecialchars($r['device_brand'] . ' ' . $r['device_model']) ?></td>
                        <td><?= htmlspecialchars($r['service_type_name'] ?? '—') ?></td>
                        <td><?= number_format($r['labor_cost']) ?></td>
                        <td><?= number_format($r['parts_total'] ?? 0) ?></td>
                        <td><?= number_format($rowTotal) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5">جمع کل</td>
                    <td><?= number_format($totalLabor) ?></td>
                    <td><?= number_format($totalParts) ?></td>
                    <td><?= number_format($grandTotal) ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;">هیچ سرویسی ثبت نشده است.</p>
    <?php endif; ?>
	
	
</div>
<div class="no-print">
    <button class="btn" onclick="window.print()">🖨️ چاپ</button>
</div>
</body>
</html>