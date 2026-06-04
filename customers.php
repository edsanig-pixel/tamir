<?php
require_once __DIR__ . '/config.php';
require_login($pdo);

// حذف مشتری
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    log_activity($pdo, $_SESSION['user_id'], 'delete', 'customer', $_GET['delete_id'], 'حذف مشتری');
    header("Location: customers.php?msg=deleted");
    exit;
}

// جستجو
$search = $_GET['search'] ?? '';
$where = '';
$params = [];
if (!empty($search)) {
    $where = " WHERE name LIKE ? OR phone LIKE ? OR company_name LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// دریافت مشتریان با آمار سریع
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(r.id) AS total_repairs,
           MAX(r.service_date) AS last_service_date
    FROM customers c
    LEFT JOIN repairs r ON c.id = r.customer_id
    $where
    GROUP BY c.id
    ORDER BY c.name
");
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>

<style>
    .page-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;
    }
    .page-title { font-size: 28px; font-weight: 800; color: #1e3a8a; }
    .search-card {
        background: #fff; border-radius: 22px; padding: 25px;
        box-shadow: 0 3px 18px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;
        margin-bottom: 25px;
    }
    .search-box {
        display: flex; gap: 10px; margin-bottom: 20px;
    }
    .search-box input {
        flex: 1; padding: 12px 16px; border: 1px solid #ccd4dd; border-radius: 12px;
        font-family: inherit;
    }
    .customer-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }
    .customer-card {
        background: #fff; border-radius: 18px; padding: 22px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid #e5e7eb;
        transition: 0.2s; position: relative;
    }
    .customer-card:hover {
        transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .customer-card h3 {
        margin: 0 0 8px 0; color: #1e3a8a; font-size: 20px;
    }
    .customer-card .info-row {
        margin: 6px 0; color: #475569; display: flex; align-items: center; gap: 6px;
    }
    .customer-card .stats-row {
        display: flex; gap: 15px; margin-top: 15px; border-top: 1px solid #eee; padding-top: 12px;
    }
    .customer-card .stat {
        text-align: center; flex: 1;
    }
    .customer-card .stat-number { font-weight: 800; color: #1e3a8a; font-size: 18px; }
    .customer-card .stat-label { font-size: 12px; color: #64748b; }
    .card-actions {
        position: absolute; top: 15px; left: 15px; display: flex; gap: 6px;
    }
    .card-actions button {
        background: #f1f5f9; border: none; border-radius: 8px; padding: 6px 12px;
        cursor: pointer; font-family: inherit; transition: 0.2s;
    }
    .card-actions button:hover { background: #e2e8f0; }
    .no-result { text-align: center; padding: 40px; color: #64748b; }
    .badge-type {
        display: inline-block; padding: 4px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 700; margin-right: 8px;
    }
    .badge-personal { background: #dbeafe; color: #1e40af; }
    .badge-company { background: #fef3c7; color: #92400e; }
</style>

<div class="page-header">
    <h2 class="page-title">👥 مدیریت مشتریان</h2>
    <button class="btn btn-primary" onclick="openCustomerModal()">➕ مشتری جدید</button>
</div>

<div class="search-card">
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="🔍 جستجوی مشتری (نام، تلفن یا شرکت)..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">جستجو</button>
        <?php if ($search): ?><a href="customers.php" class="btn secondary">حذف فیلتر</a><?php endif; ?>
    </form>

    <?php if (count($customers) > 0): ?>
        <div class="customer-grid">
            <?php foreach ($customers as $cust): 
                $typeBadge = ($cust['customer_type'] ?? 'personal') === 'company' ? 'badge-company' : 'badge-personal';
                $typeText = ($cust['customer_type'] ?? 'personal') === 'company' ? 'شرکتی' : 'شخصی';
            ?>
                <div class="customer-card">
                    <div class="card-actions">
                        <button onclick="editCustomer(<?= htmlspecialchars(json_encode($cust), ENT_QUOTES, 'UTF-8') ?>)">✏️</button>
                        <button onclick="if(confirm('آیا از حذف این مشتری مطمئن هستید؟')) window.location.href='?delete_id=<?= $cust['id'] ?>'">🗑️</button>
                    </div>
                    <h3>
                        <?= htmlspecialchars($cust['name']) ?>
                        <span class="badge-type <?= $typeBadge ?>"><?= $typeText ?></span>
                    </h3>
                    <div class="info-row">📞 <?= htmlspecialchars($cust['phone']) ?></div>
                    <?php if (!empty($cust['phone2'])): ?>
                        <div class="info-row">📱 <?= htmlspecialchars($cust['phone2']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($cust['company_name'])): ?>
                        <div class="info-row">🏢 <?= htmlspecialchars($cust['company_name']) ?></div>
                    <?php endif; ?>
                    <div class="info-row">
                        <a href="customer_profile.php?id=<?= $cust['id'] ?>" style="color:#1e3a8a; font-weight:700;">📋 پرونده کامل</a>
                    </div>
                    <div class="stats-row">
                        <div class="stat">
                            <div class="stat-number"><?= $cust['total_repairs'] ?></div>
                            <div class="stat-label">سرویس</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">
                                <?= $cust['last_service_date'] ? get_jalali_date_str(strtotime($cust['last_service_date'])) : '—' ?>
                            </div>
                            <div class="stat-label">آخرین مراجعه</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-result">هیچ مشتری‌ای یافت نشد.</div>
    <?php endif; ?>
</div>

<!-- مودال افزودن/ویرایش مشتری -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeCustomerModal()">&times;</span>
        <h3 id="customerModalTitle">➕ افزودن مشتری جدید</h3>
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

<script src="script.js"></script>
</main>
</body>
</html>