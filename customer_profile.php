<?php
require_once __DIR__ . '/config.php';
require_login($pdo);

$customerId = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) { header("Location: customers.php"); exit; }

// ================== آمار مشتری ==================
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_repairs,
        COALESCE(SUM(labor_cost),0) AS total_labor,
        COALESCE(SUM(extra_costs),0) AS total_extra,
        COALESCE(SUM(
            (SELECT COALESCE(SUM(quantity * unit_price),0) FROM repair_parts WHERE repair_id = r.id)
        ),0) AS total_parts,
        MAX(service_date) AS last_service_date
    FROM repairs r
    WHERE r.customer_id = ?
");
$statsStmt->execute([$customerId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
$totalSpent = $stats['total_labor'] + $stats['total_extra'] + $stats['total_parts'];

// ================== تاریخچه سرویس‌ها (صفحه‌بندی) ==================
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE customer_id = ?");
$countStmt->execute([$customerId]);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$historyStmt = $pdo->prepare("
    SELECT r.*, st.name AS service_type_name, dt.name AS device_type_name,
           (SELECT COALESCE(SUM(quantity * unit_price),0) FROM repair_parts WHERE repair_id = r.id) AS parts_total
    FROM repairs r
    LEFT JOIN service_types st ON r.service_type_id = st.id
    LEFT JOIN device_types dt ON r.device_type_id = dt.id
    WHERE r.customer_id = ?
    ORDER BY r.id DESC
    LIMIT ? OFFSET ?
");
$historyStmt->bindValue(1, $customerId, PDO::PARAM_INT);
$historyStmt->bindValue(2, $perPage, PDO::PARAM_INT);
$historyStmt->bindValue(3, $offset, PDO::PARAM_INT);
$historyStmt->execute();
$repairs = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>

<style>
    .page-title {
        font-size: 28px; font-weight: 800; color: #1e3a8a; margin-bottom: 20px;
        display: flex; align-items: center; gap: 15px;
    }
    .profile-card {
        background: #fff; border-radius: 22px; padding: 25px;
        box-shadow: 0 3px 18px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;
        margin-bottom: 25px;
    }
    .profile-header {
        display: flex; flex-wrap: wrap; gap: 20px; align-items: center;
    }
    .customer-avatar {
        width: 80px; height: 80px; border-radius: 50%; background: #1e3a8a;
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: 40px; font-weight: bold; flex-shrink: 0;
    }
    .customer-details {
        flex: 1;
    }
    .customer-details h3 {
        margin: 0 0 5px 0; color: #1e3a8a; font-size: 24px;
    }
    .customer-details .info-row {
        display: flex; flex-wrap: wrap; gap: 20px; margin: 8px 0;
    }
    .customer-details .info-item {
        display: flex; align-items: center; gap: 6px; color: #475569;
    }
    .badge-type {
        display: inline-block; padding: 4px 14px; border-radius: 20px;
        font-size: 12px; font-weight: 700; margin-right: 10px;
    }
    .badge-personal { background: #dbeafe; color: #1e40af; }
    .badge-company { background: #fef3c7; color: #92400e; }
    .stats-row {
        display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px;
    }
    .stat-item {
        background: #f8fafc; border-radius: 14px; padding: 15px 20px;
        text-align: center; flex: 1; min-width: 120px;
        border: 1px solid #e2e8f0;
    }
    .stat-item .stat-number { font-size: 24px; font-weight: 800; color: #1e3a8a; }
    .stat-item .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
    .service-card {
        background: #fff; border-radius: 18px; padding: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid #e5e7eb;
        margin-bottom: 15px;
    }
    .service-card .header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 15px;
    }
    .service-card .invoice { font-weight: 800; color: #1e3a8a; font-size: 18px; }
    .service-card .date { color: #6b7280; font-size: 13px; }
    .service-card .details {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    }
    .service-card .amount {
        font-weight: 700; margin-top: 10px; text-align: left;
    }
    .badge-status {
        padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 700;
    }
    .badge-paid { background: #dcfce7; color: #166534; }
    .badge-unpaid { background: #fee2e2; color: #991b1b; }
    .badge-partial { background: #fef3c7; color: #92400e; }
    .actions-bar {
        display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;
    }
    .btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 22px; border-radius: 40px; font-weight: 700;
        text-decoration: none; transition: 0.2s; cursor: pointer; border: none;
        font-family: inherit; font-size: 14px;
    }
    .btn-primary { background: #1e3c72; color: #fff; }
    .btn-primary:hover { background: #172d56; }
    .btn-outline {
        background: #fff; color: #1e3c72; border: 2px solid #1e3c72;
    }
    .btn-outline:hover { background: #f0f2f5; }
    .btn-danger { background: #dc2626; color: #fff; }
    .btn-danger:hover { background: #b91c1c; }
</style>

<h2 class="page-title">
    👤 پرونده مشتری
    <a href="customers.php" class="btn btn-outline" style="font-size:14px;">← بازگشت به لیست مشتریان</a>
</h2>

<!-- کارت اطلاعات مشتری -->
<div class="profile-card">
    <div class="profile-header">
        <div class="customer-avatar"><?= mb_substr($customer['name'], 0, 1) ?></div>
        <div class="customer-details">
            <h3>
                <?= htmlspecialchars($customer['name']) ?>
                <span class="badge-type <?= ($customer['customer_type'] ?? 'personal') === 'company' ? 'badge-company' : 'badge-personal' ?>">
                    <?= ($customer['customer_type'] ?? 'personal') === 'company' ? 'شرکتی' : 'شخصی' ?>
                </span>
            </h3>
            <div class="info-row">
                <span class="info-item">📞 <?= htmlspecialchars($customer['phone']) ?></span>
                <?php if (!empty($customer['phone2'])): ?>
                    <span class="info-item">📱 <?= htmlspecialchars($customer['phone2']) ?></span>
                <?php endif; ?>
                <?php if (!empty($customer['email'])): ?>
                    <span class="info-item">✉️ <?= htmlspecialchars($customer['email']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($customer['company_name'])): ?>
                <div class="info-row">
                    <span class="info-item">🏢 <?= htmlspecialchars($customer['company_name']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($customer['address'])): ?>
                <div class="info-row">
                    <span class="info-item">📍 <?= htmlspecialchars($customer['address']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($customer['notes'])): ?>
                <div class="info-row" style="margin-top:5px;">
                    <span class="info-item">📝 <?= htmlspecialchars($customer['notes']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- کارت‌های آماری -->
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-number"><?= $stats['total_repairs'] ?></div>
            <div class="stat-label">کل سرویس‌ها</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= format_rial($totalSpent) ?></div>
            <div class="stat-label">مجموع هزینه‌ها</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">
                <?= $stats['last_service_date'] ? get_jalali_date_str(strtotime($stats['last_service_date'])) : '—' ?>
            </div>
            <div class="stat-label">آخرین سرویس</div>
        </div>
    </div>

    <div class="actions-bar">
        <button class="btn btn-outline" onclick="editCustomer(<?= htmlspecialchars(json_encode($customer), ENT_QUOTES, 'UTF-8') ?>)">✏️ ویرایش اطلاعات</button>
        <a href="customer_print.php?id=<?= $customerId ?>" class="btn btn-primary" target="_blank">🖨️ چاپ گزارش</a>
    </div>
</div>

<!-- تاریخچه سرویس‌ها -->
<h3 style="margin-bottom:15px;">📋 تاریخچه سرویس‌ها</h3>

<?php if (count($repairs) > 0): ?>
    <?php foreach ($repairs as $r): 
        $partsTotal = $r['parts_total'] ?? 0;
        $total = $r['labor_cost'] + $r['extra_costs'] + $partsTotal;
        $badgeClass = match($r['payment_status']) {
            'paid' => 'badge-paid',
            'partial' => 'badge-partial',
            default => 'badge-unpaid'
        };
        $paymentText = match($r['payment_status']) {
            'paid' => 'پرداخت شده',
            'partial' => 'پرداخت ناقص',
            default => 'پرداخت نشده'
        };
    ?>
        <div class="service-card">
            <div class="header">
                <div>
                    <span class="invoice"><?= htmlspecialchars($r['invoice_number']) ?></span>
                    <span class="date"> | <?= $r['service_date'] ? get_jalali_date_str(strtotime($r['service_date'])) : '—' ?></span>
                </div>
                <span class="badge-status <?= $badgeClass ?>"><?= $paymentText ?></span>
            </div>
            <div class="details">
                <div><strong>دستگاه:</strong> <?= htmlspecialchars($r['device_brand'] . ' ' . $r['device_model']) ?></div>
                <div><strong>نوع خدمت:</strong> <?= htmlspecialchars($r['service_type_name'] ?? '—') ?></div>
                <div><strong>مشکل:</strong> <?= htmlspecialchars(mb_substr($r['fault_description'] ?? '', 0, 80)) ?></div>
            </div>
            <div class="amount">
                مزد: <?= format_rial($r['labor_cost']) ?> | قطعات: <?= format_rial($partsTotal) ?> | <strong>جمع: <?= format_rial($total) ?></strong>
            </div>
            <div class="actions-bar" style="justify-content:flex-start;">
                <a href="invoice.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-small">🧾 فاکتور</a>
                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-small">✏️ ویرایش</a>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="justify-content:center; gap:6px; margin-top:20px;">
            <?php
            $base = "?id=$customerId&";
            if ($page > 1) {
                echo '<a href="'.$base.'page=1">⏮️</a> ';
                echo '<a href="'.$base.'page='.($page-1).'">◀</a> ';
            }
            for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++) {
                $active = $i == $page ? 'active' : '';
                echo '<a href="'.$base.'page='.$i.'" class="page-link '.$active.'">'.$i.'</a> ';
            }
            if ($page < $totalPages) {
                echo '<a href="'.$base.'page='.($page+1).'">▶</a> ';
                echo '<a href="'.$base.'page='.$totalPages.'">⏭️</a>';
            }
            ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <p style="text-align:center; color:#666;">هیچ سرویسی برای این مشتری ثبت نشده است.</p>
<?php endif; ?>

<h3 style="margin-top:30px;">💰 تراکنش‌های مالی</h3>
<?php
$transStmt = $pdo->prepare("SELECT * FROM transactions WHERE customer_id = ? ORDER BY date DESC");
$transStmt->execute([$customerId]);
$transactions = $transStmt->fetchAll();
?>
<?php if (count($transactions) > 0): ?>
    <table>
        <thead><tr><th>تاریخ</th><th>مبلغ</th><th>توضیح</th></tr></thead>
        <tbody>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= get_jalali_date_str(strtotime($t['date'])) ?></td>
                    <td style="color:<?= $t['type'] === 'income' ? '#16a34a' : '#dc2626' ?>">
                        <?= $t['type'] === 'income' ? '+' : '-' ?><?= format_rial($t['amount']) ?>
                    </td>
                    <td><?= htmlspecialchars($t['description']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>تراکنشی ثبت نشده است.</p>
<?php endif; ?>
<button class="btn primary" onclick="openTransactionModal(<?= $customerId ?>)">➕ ثبت پرداخت جدید</button>


<!-- مودال ویرایش مشتری (همان modal-add-item با تغییرات) -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeCustomerModal()">&times;</span>
        <h3 id="customerModalTitle">✏️ ویرایش مشتری</h3>
        <form id="customerForm">
            <input type="hidden" id="custId">
            <div class="form-row">
                <input type="text" id="custName" placeholder="نام و نام خانوادگی *" required>
            </div>
            <div class="form-row">
                <input type="text" id="custPhone" placeholder="تلفن اصلی *" required>
                <input type="text" id="custPhone2" placeholder="تلفن ثانویه">
            </div>
            <div class="form-row">
                <select id="custType">
                    <option value="personal">شخصی</option>
                    <option value="company">شرکتی / مجتمع</option>
                </select>
                <input type="text" id="custCompany" placeholder="نام شرکت / مجتمع">
            </div>
            <div class="form-row">
                <input type="text" id="custAddress" placeholder="آدرس">
            </div>
            <div class="form-row">
                <input type="email" id="custEmail" placeholder="ایمیل">
            </div>
            <div class="form-row">
                <textarea id="custNotes" placeholder="توضیحات"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCustomerModal()">انصراف</button>
                <button type="submit" class="btn btn-primary">💾 ذخیره</button>
            </div>
        </form>
    </div>
</div>
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeTransactionModal()">&times;</span>
        <h3>➕ ثبت دریافت از مشتری</h3>
        <form id="transactionForm">
            <input type="hidden" id="transCustomerId" value="<?= $customerId ?>">
            <input type="text" id="transAmount" placeholder="مبلغ (ریال)" oninput="formatInput(this)" required>
            <input type="text" id="transDate" class="datepicker-input" readonly placeholder="تاریخ">
            <input type="hidden" id="transDateHidden">
            <select id="transRepair">
                <option value="">بابت فاکتور (اختیاری)</option>
                <?php foreach ($repairs as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['invoice_number']) ?></option>
                <?php endforeach; ?>
            </select>
            <textarea id="transDesc" placeholder="توضیحات"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeTransactionModal()">انصراف</button>
                <button type="submit" class="btn primary">ثبت</button>
            </div>
        </form>
    </div>
</div>
<script src="script.js"></script>
</main>
</body>
</html>