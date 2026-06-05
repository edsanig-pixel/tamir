<?php
require_once __DIR__ . '/../includes/config.php';
require_login($pdo);

// حذف مشتری
$deleteId = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
if ($deleteId && $deleteId > 0) {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$deleteId]);
    log_activity($pdo, $_SESSION['user_id'], 'delete', 'customer', $deleteId, 'حذف مشتری');
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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2 class="page-title">👥 مدیریت مشتریان</h2>
    <button class="btn btn-primary" onclick="openCustomerModal()">➕ مشتری جدید</button>
</div>

<div class="search-card">
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="🔍 جستجوی مشتری (نام، تلفن یا شرکت)..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">جستجو</button>
        <?php if ($search): ?><a href="<?= BASE_URL ?>/customers" class="btn secondary">حذف فیلتر</a><?php endif; ?>
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
                        <a href="customer_profile.php?id=<?= $cust['id'] ?>" class="card-link">📋 پرونده کامل</a>
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
</main>
</body>
</html>