<?php
require_once __DIR__ . '/config.php';
require_login($pdo);

if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    header("Location: index.php");
    exit;
}

$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();

$filter_user   = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_entity = $_GET['entity'] ?? '';
$search        = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;

$where = [];
$params = [];
if (!empty($filter_user)) { $where[] = "l.user_id = ?"; $params[] = $filter_user; }
if (!empty($filter_action)) { $where[] = "l.action = ?"; $params[] = $filter_action; }
if (!empty($filter_entity)) { $where[] = "l.entity_type = ?"; $params[] = $filter_entity; }
if (!empty($search)) { $where[] = "l.description LIKE ?"; $params[] = "%$search%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log l $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT l.*, u.full_name AS user_name
    FROM activity_log l
    LEFT JOIN users u ON l.user_id = u.id
    $whereSql
    ORDER BY l.id DESC
    LIMIT ? OFFSET ?
");

$paramIndex = 1;
foreach ($params as $value) {
    $stmt->bindValue($paramIndex++, $value, PDO::PARAM_STR);
}
$stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(action = 'create') AS creates,
        SUM(action = 'update') AS updates,
        SUM(action = 'delete') AS deletes,
        SUM(action = 'login')  AS logins,
        SUM(action = 'logout') AS logouts
    FROM activity_log
")->fetch();

$entityLabels = [
    'repair'       => 'سرویس',
    'customer'     => 'مشتری',
    'part'         => 'قطعه',
    'user'         => 'کاربر',
    'device_type'  => 'نوع دستگاه',
    'service_type' => 'نوع خدمت',
    'technician'   => 'تکنسین',
];

function formatDescription($description, $entityType, $action) {
    if ($entityType === 'repair' && in_array($action, ['update', 'delete'])) {
        $data = json_decode($description, true);
        if ($data && isset($data['invoice_number'])) {
            $invoice = htmlspecialchars($data['invoice_number']);
            $actionText = ($action === 'delete') ? 'حذف' : 'ویرایش';
            return "فاکتور $invoice ($actionText شد)";
        }
    }
    return mb_strlen($description) > 80 ? mb_substr($description, 0, 80) . '...' : $description;
}

require_once __DIR__ . '/header.php';
?>

<style>
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
.stat-card { background: #fff; border-radius: 14px; padding: 18px 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #eef2f7; text-align: center; }
.stat-icon { font-size: 28px; margin-bottom: 8px; }
.stat-number { font-size: 28px; font-weight: 800; color: #1e3a8a; }
.stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
.log-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.log-table th { background: #f8fafc; color: #1e3a8a; font-weight: 700; padding: 14px 12px; text-align: right; border-bottom: 2px solid #e2e8f0; }
.log-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.log-table tr:hover td { background: #fafbff; }
.badge-action { display: inline-block; padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 700; }
.badge-create { background: #dcfce7; color: #166534; }
.badge-update { background: #fef3c7; color: #92400e; }
.badge-delete { background: #fee2e2; color: #991b1b; }
.badge-login  { background: #dbeafe; color: #1e40af; }
.badge-logout { background: #f1f5f9; color: #475569; }
.filter-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 20px; }
.filter-form select, .filter-form input { padding: 10px 14px; border: 1px solid #ccd4dd; border-radius: 10px; background: #fff; font-family: inherit; }
.filter-form button { padding: 10px 20px; }
.pagination { display: flex; justify-content: center; gap: 6px; margin-top: 20px; }
.pagination a, .pagination span { display: inline-block; padding: 8px 16px; border-radius: 8px; text-decoration: none; background: #fff; border: 1px solid #ccd4dd; color: #1e3a8a; font-weight: 600; }
.pagination a.active, .pagination span.active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }
.restore-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); backdrop-filter: blur(4px); justify-content: center; align-items: center; }
.restore-modal.active { display: flex; }
.restore-modal-content { background: #fff; border-radius: 20px; width: 90%; max-width: 700px; max-height: 85vh; overflow-y: auto; padding: 30px; position: relative; }
.restore-modal-close { position: absolute; top: 15px; right: 20px; font-size: 1.8rem; cursor: pointer; color: #7f8c8d; background: none; border: none; }
</style>

<h2>📋 گزارش فعالیت‌های سیستم</h2>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon">📊</div><div class="stat-number"><?= number_format($stats['total']) ?></div><div class="stat-label">کل رویدادها</div></div>
    <div class="stat-card"><div class="stat-icon">➕</div><div class="stat-number"><?= number_format($stats['creates']) ?></div><div class="stat-label">ایجاد</div></div>
    <div class="stat-card"><div class="stat-icon">✏️</div><div class="stat-number"><?= number_format($stats['updates']) ?></div><div class="stat-label">ویرایش</div></div>
    <div class="stat-card"><div class="stat-icon">🗑️</div><div class="stat-number"><?= number_format($stats['deletes']) ?></div><div class="stat-label">حذف</div></div>
    <div class="stat-card"><div class="stat-icon">🔑</div><div class="stat-number"><?= number_format($stats['logins']) ?></div><div class="stat-label">ورود</div></div>
    <div class="stat-card"><div class="stat-icon">🚪</div><div class="stat-number"><?= number_format($stats['logouts']) ?></div><div class="stat-label">خروج</div></div>
</div>

<div class="card">
    <form method="GET" class="filter-form">
        <select name="user">
            <option value="">👤 همه کاربران</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="action">
            <option value="">⚡ همه عملیات‌ها</option>
            <option value="create" <?= $filter_action == 'create' ? 'selected' : '' ?>>ایجاد</option>
            <option value="update" <?= $filter_action == 'update' ? 'selected' : '' ?>>ویرایش</option>
            <option value="delete" <?= $filter_action == 'delete' ? 'selected' : '' ?>>حذف</option>
            <option value="login"  <?= $filter_action == 'login' ? 'selected' : '' ?>>ورود</option>
            <option value="logout" <?= $filter_action == 'logout' ? 'selected' : '' ?>>خروج</option>
        </select>
        <select name="entity">
            <option value="">🗂️ همه موجودیت‌ها</option>
            <?php foreach ($entityLabels as $value => $label): ?>
                <option value="<?= $value ?>" <?= $filter_entity == $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" placeholder="🔍 جستجو در توضیحات..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn primary">اعمال فیلتر</button>
        <a href="logs.php" class="btn secondary">حذف فیلتر</a>
    </form>

    <table class="log-table">
        <thead>
            <tr>
                <th>#</th>
                <th>کاربر</th>
                <th>نوع عملیات</th>
                <th>موجودیت</th>
                <th>شناسه</th>
                <th>توضیحات</th>
                <th>تاریخ</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($logs)): ?>
                <?php foreach ($logs as $i => $log): 
                    $badgeClass = match($log['action']) {
                        'create' => 'badge-create', 'update' => 'badge-update', 'delete' => 'badge-delete',
                        'login'  => 'badge-login',  'logout' => 'badge-logout', default  => ''
                    };
                    $actionText = match($log['action']) {
                        'create' => 'ایجاد', 'update' => 'ویرایش', 'delete' => 'حذف',
                        'login'  => 'ورود',  'logout' => 'خروج', default  => $log['action']
                    };
                    $entityName = $entityLabels[$log['entity_type'] ?? ''] ?? ($log['entity_type'] ?? '—');
                    $descDisplay = formatDescription($log['description'] ?? '', $log['entity_type'] ?? '', $log['action'] ?? '');
                ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= htmlspecialchars($log['user_name'] ?? '—') ?></td>
                        <td><span class="badge-action <?= $badgeClass ?>"><?= $actionText ?></span></td>
                        <td><?= htmlspecialchars($entityName) ?></td>
                        <td><?= $log['entity_id'] ?? '—' ?></td>
                        <td><?= htmlspecialchars($descDisplay) ?></td>
                        <td><?= get_jalali_date_str(strtotime($log['created_at'])) ?></td>
                        <td>
                            <?php if ($log['entity_type'] === 'repair' && in_array($log['action'], ['update', 'delete'])): ?>
                                <button class="btn small warning" onclick="previewRestore(<?= $log['id'] ?>)">↩ بازگردانی</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center; padding:30px;">هیچ رویدادی ثبت نشده است.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $baseUrl = "?";
            if (!empty($filter_user)) $baseUrl .= "user=$filter_user&";
            if (!empty($filter_action)) $baseUrl .= "action=$filter_action&";
            if (!empty($filter_entity)) $baseUrl .= "entity=$filter_entity&";
            if (!empty($search)) $baseUrl .= "search=" . urlencode($search) . "&";
            
            if ($page > 1) {
                echo '<a href="' . $baseUrl . 'page=1">⏮️</a>';
                echo '<a href="' . $baseUrl . 'page=' . ($page - 1) . '">◀</a>';
            }
            for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo '<a href="' . $baseUrl . 'page=' . $i . '" class="' . $active . '">' . $i . '</a>';
            }
            if ($page < $totalPages) {
                echo '<a href="' . $baseUrl . 'page=' . ($page + 1) . '">▶</a>';
                echo '<a href="' . $baseUrl . 'page=' . $totalPages . '">⏭️</a>';
            }
            ?>
            <span style="margin-right:10px;">صفحه <?= $page ?> از <?= $totalPages ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- مودال بازگردانی -->
<div id="restoreModal" class="restore-modal">
    <div class="restore-modal-content">
        <button class="restore-modal-close" onclick="closeRestoreModal()">&times;</button>
        <h3>📋 پیش‌نمایش بازگردانی</h3>
        <div id="restoreDetails">در حال بارگذاری...</div>
        <div class="modal-actions" style="margin-top:20px; text-align:left;">
            <button class="btn secondary" onclick="closeRestoreModal()">انصراف</button>
            <button class="btn primary" id="confirmRestoreBtn">تأیید و بازگردانی</button>
        </div>
    </div>
</div>

<script>
let currentRestoreLogId = null;

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.remove('active');
    // دیگر ID را پاک نمی‌کنیم تا بعداً استفاده شود
    // currentRestoreLogId = null;  ← این خط حذف شد
}

function previewRestore(logId) {
    currentRestoreLogId = logId;
    document.getElementById('restoreModal').classList.add('active');
    document.getElementById('restoreDetails').innerHTML = 'در حال بارگذاری...';

    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_log_data&log_id=' + logId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const repair = data.repair;
            if (!repair) {
                document.getElementById('restoreDetails').innerHTML = '<p style="color:red;">اطلاعات قبلی یافت نشد.</p>';
                return;
            }
            let html = '<table style="width:100%; font-size:14px;">';
            html += `<tr><td>🔢 فاکتور</td><td>${repair.invoice_number || '—'}</td></tr>`;
            html += `<tr><td>👤 مشتری</td><td>${repair.customer_name || repair.customer_id || '—'}</td></tr>`;
            html += `<tr><td>📱 مدل</td><td>${repair.device_brand || '—'} ${repair.device_model || ''}</td></tr>`;
            html += `<tr><td>🔢 سریال</td><td>${repair.device_serial || '—'}</td></tr>`;
            html += `<tr><td>📅 تاریخ سرویس</td><td>${repair.service_date_jalali || repair.service_date || '—'}</td></tr>`;
            html += `<tr><td>💰 مزد دست</td><td>${Number(repair.labor_cost).toLocaleString()} ریال</td></tr>`;
            html += `<tr><td>⚙️ هزینه جانبی</td><td>${Number(repair.extra_costs).toLocaleString()} ریال</td></tr>`;
            html += `<tr><td>📝 شرح مشکل</td><td>${repair.fault_description || '—'}</td></tr>`;
            html += '</table>';
            document.getElementById('restoreDetails').innerHTML = html;
        } else {
            document.getElementById('restoreDetails').innerHTML = '<p style="color:red;">خطا: ' + data.message + '</p>';
        }
    })
    .catch(err => {
        document.getElementById('restoreDetails').innerHTML = '<p style="color:red;">خطا در ارتباط با سرور</p>';
    });
}

document.getElementById('confirmRestoreBtn').addEventListener('click', function() {
    const idToRestore = currentRestoreLogId; // قبل از هر چیز ذخیره کن
    if (!idToRestore) return;

    if (!confirm('آیا از بازگردانی این سرویس به حالت قبل مطمئن هستید؟')) return;

    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=restore_repair&log_id=' + idToRestore
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + (data.message || 'خطا در بازگردانی'));
        }
    });
});

// بستن مودال با کلیک بیرون
window.addEventListener('click', function(event) {
    const modal = document.getElementById('restoreModal');
    if (event.target === modal) closeRestoreModal();
});
</script>
</main>
</body>
</html>