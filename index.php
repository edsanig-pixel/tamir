<?php
require_once __DIR__ . '/config.php';
require_login($pdo);

// ================= آمار کلی =================
$totalRepairs = $pdo->query("SELECT COUNT(*) FROM repairs")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalParts = $pdo->query("SELECT COUNT(*) FROM parts")->fetchColumn();
$totalTechnicians = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();

$totalRevenue = $pdo->query("
SELECT
COALESCE(SUM(r.labor_cost),0)
+
COALESCE((SELECT SUM(quantity * unit_price) FROM repair_parts),0)
FROM repairs r
")->fetchColumn();

$totalCosts = $pdo->query("
SELECT
COALESCE(SUM(r.extra_costs),0)
+
COALESCE((SELECT SUM(quantity * purchase_price) FROM repair_parts),0)
FROM repairs r
")->fetchColumn();

$profit = $totalRevenue - $totalCosts;

$paidCount = $pdo->query("SELECT COUNT(*) FROM repairs WHERE payment_status='paid'")->fetchColumn();
$unpaidCount = $pdo->query("SELECT COUNT(*) FROM repairs WHERE payment_status='unpaid'")->fetchColumn();

// ================= آخرین سرویس‌ها =================
$latestRepairs = $pdo->query("
SELECT
r.*, c.name AS customer_name,
dt.name AS device_type,
st.name AS service_type
FROM repairs r
LEFT JOIN customers c ON r.customer_id = c.id
LEFT JOIN device_types dt ON r.device_type_id = dt.id
LEFT JOIN service_types st ON r.service_type_id = st.id
ORDER BY r.id DESC
LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// ================= سرویس های ماه جاری =================
$currentMonth = date('m');
$currentYear = date('Y');

$monthlyRepairs = $pdo->prepare("
SELECT COUNT(*)
FROM repairs
WHERE MONTH(service_date)=?
AND YEAR(service_date)=?
");
$monthlyRepairs->execute([$currentMonth, $currentYear]);
$monthlyCount = $monthlyRepairs->fetchColumn();

require_once __DIR__ . '/header.php';
?>

<style>
.dashboard-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:20px;
    margin-bottom:25px;
}

.dashboard-card{
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 4px 15px rgba(0,0,0,.05);
    border:1px solid #edf0f5;
    position:relative;
    overflow:hidden;
}

.dashboard-card::before{
    content:'';
    position:absolute;
    top:0;
    right:0;
    width:6px;
    height:100%;
    background:#1e40af;
}

.dashboard-icon{
    font-size:32px;
    margin-bottom:12px;
}

.dashboard-title{
    color:#666;
    font-size:14px;
    margin-bottom:10px;
}

.dashboard-value{
    font-size:30px;
    font-weight:800;
    color:#1e293b;
}

.dashboard-success::before{
    background:#16a34a;
}

.dashboard-danger::before{
    background:#dc2626;
}

.dashboard-warning::before{
    background:#f59e0b;
}

.dashboard-section{
    background:#fff;
    border-radius:18px;
    padding:25px;
    margin-bottom:25px;
    border:1px solid #edf0f5;
    box-shadow:0 4px 15px rgba(0,0,0,.04);
}

.dashboard-section h3{
    margin-bottom:20px;
    color:#1e3a8a;
}

.quick-actions{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:15px;
}

.quick-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    background:#f8fafc;
    border:1px solid #dbe2ea;
    border-radius:14px;
    padding:18px;
    text-decoration:none;
    color:#1e293b;
    font-weight:700;
    transition:.2s;
}

.quick-btn:hover{
    background:#1e40af;
    color:#fff;
    transform:translateY(-2px);
}

.status-badge{
    padding:6px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
}

.status-paid{
    background:#dcfce7;
    color:#166534;
}

.status-unpaid{
    background:#fee2e2;
    color:#991b1b;
}
</style>

<h2>📊 داشبورد مدیریت تعمیرگاه</h2>

<div class="dashboard-grid">

    <div class="dashboard-card">
        <div class="dashboard-icon">🛠️</div>
        <div class="dashboard-title">کل سرویس‌ها</div>
        <div class="dashboard-value"><?= number_format($totalRepairs) ?></div>
    </div>

    <div class="dashboard-card dashboard-success">
        <div class="dashboard-icon">💰</div>
        <div class="dashboard-title">درآمد کل</div>
        <div class="dashboard-value" style="font-size:22px">
            <?= format_rial($totalRevenue) ?>
        </div>
    </div>

    <div class="dashboard-card dashboard-danger">
        <div class="dashboard-icon">📉</div>
        <div class="dashboard-title">هزینه کل</div>
        <div class="dashboard-value" style="font-size:22px">
            <?= format_rial($totalCosts) ?>
        </div>
    </div>

    <div class="dashboard-card dashboard-warning">
        <div class="dashboard-icon">📈</div>
        <div class="dashboard-title">سود تقریبی</div>
        <div class="dashboard-value" style="font-size:22px">
            <?= format_rial($profit) ?>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="dashboard-icon">👤</div>
        <div class="dashboard-title">تعداد مشتریان</div>
        <div class="dashboard-value"><?= number_format($totalCustomers) ?></div>
    </div>

    <div class="dashboard-card">
        <div class="dashboard-icon">⚙️</div>
        <div class="dashboard-title">قطعات ثبت شده</div>
        <div class="dashboard-value"><?= number_format($totalParts) ?></div>
    </div>

    <div class="dashboard-card dashboard-success">
        <div class="dashboard-icon">✅</div>
        <div class="dashboard-title">پرداخت شده</div>
        <div class="dashboard-value"><?= number_format($paidCount) ?></div>
    </div>

    <div class="dashboard-card dashboard-danger">
        <div class="dashboard-icon">❌</div>
        <div class="dashboard-title">پرداخت نشده</div>
        <div class="dashboard-value"><?= number_format($unpaidCount) ?></div>
    </div>

</div>

<div class="dashboard-section">
    <h3>⚡ دسترسی سریع</h3>

    <div class="quick-actions">
        <a class="quick-btn" href="create.php">➕ ثبت سرویس</a>
        <a class="quick-btn" href="reports.php">📋 لیست سرویس‌ها</a>
        <a class="quick-btn" href="customers.php">👥 مشتریان</a>
        <a class="quick-btn" href="parts.php">⚙️ قطعات</a>
        <a class="quick-btn" href="customer_profile.php">👥 لیست مشتریان </a>
    </div>
</div>

<div class="dashboard-section">

    <h3>🕒 آخرین سرویس‌ها</h3>

    <table>
        <thead>
            <tr>
                <th>شماره فاکتور</th>
                <th>مشتری</th>
                <th>دستگاه</th>
                <th>نوع سرویس</th>
                <th>وضعیت پرداخت</th>
                <th>تاریخ</th>
                <th>عملیات</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($latestRepairs as $item): ?>

            <tr>
                <td><?= htmlspecialchars($item['invoice_number']) ?></td>
                <td><a href="customer_profile.php?id=<?= $item['customer_id'] ?>" style="color:#1e3a8a; font-weight:700;"><?= htmlspecialchars($item['customer_name']) ?></a></td>
                <td><?= htmlspecialchars($item['device_type'] ?? '-') ?></td>
                <td><?= htmlspecialchars($item['service_type'] ?? '-') ?></td>
                <td>
                    <?php if($item['payment_status']=='paid'): ?>
                        <span class="status-badge status-paid">پرداخت شده</span>
                    <?php else: ?>
                        <span class="status-badge status-unpaid">پرداخت نشده</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $item['service_date'] ?>
                </td>
                <td>
                    <a class="btn small primary" href="invoice.php?id=<?= $item['id'] ?>">
                        مشاهده فاکتور
                    </a>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

</div>

<div class="dashboard-section">
    <h3>📌 خلاصه عملکرد</h3>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">

        <div class="card">
            <div class="card-title">سرویس‌های این ماه</div>
            <div style="font-size:35px;font-weight:800;color:#1e40af;">
                <?= number_format($monthlyCount) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">تعداد تکنسین‌ها</div>
            <div style="font-size:35px;font-weight:800;color:#1e40af;">
                <?= number_format($totalTechnicians) ?>
            </div>
        </div>

    </div>
</div>
<?php
$lowStockParts = $pdo->query("SELECT * FROM parts WHERE stock < 5 ORDER BY stock ASC")->fetchAll();
if (count($lowStockParts) > 0):
?>
<div class="dashboard-section" style="border-right: 4px solid #dc2626;">
    <h3>⚠️ قطعات با موجودی کم</h3>
    <table>
        <thead>
            <tr><th>قطعه</th><th>موجودی فعلی</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lowStockParts as $lp): ?>
            <tr>
                <td><?= htmlspecialchars($lp['name']) ?></td>
                <td style="color:#dc2626; font-weight:bold;"><?= $lp['stock'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
</main>
</body>
</html>
