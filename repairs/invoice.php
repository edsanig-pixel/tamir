<?php
require_once __DIR__ . '/../includes/config.php';
require_login($pdo);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
SELECT 
    r.*,
    c.name AS customer_name,
    c.phone AS customer_phone,
    t.name AS technician_name,
    dt.name AS device_type,
    st.name AS service_type
FROM repairs r
LEFT JOIN customers c ON r.customer_id = c.id
LEFT JOIN technicians t ON r.technician_id = t.id
LEFT JOIN device_types dt ON r.device_type_id = dt.id
LEFT JOIN service_types st ON r.service_type_id = st.id
WHERE r.id = ?
");

$stmt->execute([$id]);

$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die('فاکتور یافت نشد');
}

/* قطعات */

$partsStmt = $pdo->prepare("
SELECT 
    p.name,
    rp.quantity,
    rp.unit_price
FROM repair_parts rp
JOIN parts p ON rp.part_id = p.id
WHERE rp.repair_id = ?
");

$partsStmt->execute([$id]);

$parts = $partsStmt->fetchAll(PDO::FETCH_ASSOC);

/* محاسبات */

$totalParts = 0;

foreach ($parts as $p) {
    $totalParts += $p['quantity'] * $p['unit_price'];
}

$laborCost  = (int)$invoice['labor_cost'];
$extraCosts = (int)$invoice['extra_costs'];

$grandTotal = $laborCost + $extraCosts + $totalParts;

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

<meta charset="UTF-8">

<title>
فاکتور <?= htmlspecialchars($invoice['invoice_number']) ?>
</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/invoice.css">
</head>

<body>

<div class="invoice-page">

    <!-- HEADER -->

    <div class="header">

        <div class="header-right">

            <h1>فاکتور خدمات تعمیرات</h1>

            <p>
                سیستم مدیریت خدمات تعمیراتی
            </p>

        </div>

        <div class="header-left">

            <?php
$isPaid = ($invoice['payment_status'] ?? '') === 'paid';
?>

<div class="status-badge <?= $isPaid ? 'paid' : 'unpaid' ?>">
    <?= $isPaid ? 'پرداخت شده' : 'پرداخت نشده' ?>
</div>

            <div>
                <strong>شماره فاکتور:</strong>
                <?= htmlspecialchars($invoice['invoice_number']) ?>
            </div>

            <div>
                <strong>تاریخ:</strong>

                <?= $invoice['service_date']
                    ? get_jalali_date_str(strtotime($invoice['service_date']))
                    : '---' ?>
            </div>

        </div>

    </div>

    <!-- INFO -->

    <div class="grid">

        <div class="box">

            <h3>اطلاعات مشتری</h3>

            <p>
                <strong>نام مشتری:</strong>
                <a href="customer_profile.php?id=<?= $invoice['customer_id'] ?>" style="color:#1e3a8a; font-weight:700;"><?= htmlspecialchars($invoice['customer_name']) ?></a>
            </p>

            <p>
                <strong>تلفن:</strong>
                <?= htmlspecialchars($invoice['customer_phone']) ?>
            </p>

            <p>
                <strong>آدرس:</strong>
                <?= htmlspecialchars($invoice['device_location']) ?>
            </p>

        </div>

        <div class="box">

            <h3>اطلاعات سرویس</h3>

            <p>
                <strong>تکنسین:</strong>
                <?= htmlspecialchars($invoice['technician_name']) ?>
            </p>

            <p>
                <strong>نوع دستگاه:</strong>
                <?= htmlspecialchars($invoice['device_type']) ?>
            </p>

            <p>
                <strong>نوع خدمت:</strong>
                <?= htmlspecialchars($invoice['service_type']) ?>
            </p>

        </div>

    </div>

    <!-- PROBLEM -->

    <div class="double-section">

    <div class="section-box">
        <div class="section-title">شرح مشکل</div>

        <div class="box">
            <?= nl2br(htmlspecialchars($invoice['fault_description'] ?? '---')) ?>
        </div>
    </div>

    <div class="section-box">
        <div class="section-title">اقدامات انجام شده</div>

        <div class="box">
            <?= nl2br(htmlspecialchars($invoice['solution_description'] ?? '---')) ?>
        </div>
    </div>

</div>

    <!-- TABLE -->

    <?php if(!empty($parts)): ?>

    <table class="parts-table">

        <thead>

        <tr>

            <th>#</th>
            <th>قطعه</th>
            <th>تعداد</th>
            <th>فی</th>
            <th>جمع</th>

        </tr>

        </thead>

        <tbody>

        <?php foreach($parts as $index => $part): ?>

            <?php
            $rowTotal =
                $part['quantity']
                *
                $part['unit_price'];
            ?>

            <tr>

                <td><?= $index + 1 ?></td>

                <td>
                    <?= htmlspecialchars($part['name']) ?>
                </td>

                <td>
                    <?= number_format($part['quantity']) ?>
                </td>

                <td>
                    <?= number_format($part['unit_price']) ?> ریال
                </td>

                <td>
                    <?= number_format($rowTotal) ?> ریال
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <?php endif; ?>

    <!-- TOTALS -->

    <div class="totals-wrapper">

    <div class="mini-totals">

        <div class="mini-card">
            <div class="mini-title">اجرت خدمات</div>
            <div class="mini-value">
                <?= format_rial($invoice['labor_cost']) ?>
            </div>
        </div>

        <div class="mini-card">
            <div class="mini-title">هزینه جانبی</div>
            <div class="mini-value">
                <?= format_rial($invoice['extra_costs']) ?>
            </div>
        </div>

        <div class="mini-card">
            <div class="mini-title">مجموع قطعات</div>
            <div class="mini-value">
                <?= format_rial($totalParts) ?>
            </div>
        </div>

    </div>

    <div class="grand-total">
        <span>جمع کل</span>

        <strong>
            <?= format_rial($grandTotal) ?>
        </strong>
    </div>

</div>

    <!-- FOOTER -->

    <div class="footer">

        با تشکر از اعتماد شما ❤️

        <br>

        خدمات تعمیرات جک پارکینگ

        <br>

        09133530345

    </div>

</div>

<div class="no-print">

    <button
        class="print-btn"
        onclick="window.print();"
    >
        🖨 چاپ / ذخیره PDF
    </button>

</div>

</body>
</html>