<?php
require_once __DIR__ . '/config.php';
require_login($pdo);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

// ---------- 1. دریافت تمام ستون‌های رکورد ----------
$stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { header("Location: index.php"); exit; }

// نگه‌داشتن یک کپی از داده‌های قبلی برای لاگ
$oldData = $row;
$message = '';

// ---------- 2. پردازش فرم ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // جایگزینی مقادیر جدید در آرایهٔ $row
    $row['customer_id']         = $_POST['customer_id'] ?: null;
    $row['device_type_id']      = $_POST['device_type_id'] ?: null;
    $row['device_brand']        = $_POST['device_brand'] ?? '';
    $row['device_model']        = $_POST['device_model'] ?? '';
    $row['device_serial']       = $_POST['device_serial'] ?? '';
    $row['device_year']         = $_POST['device_year'] !== '' ? (int)$_POST['device_year'] : null;
    $row['device_location']     = $_POST['device_location'] ?? '';
    $row['service_type_id']     = $_POST['service_type_id'] ?: null;
    $row['problem_part']        = $_POST['problem_part'] ?? '';
    $row['fault_description']   = $_POST['fault_description'] ?? '';
    $row['technician_id']       = $_POST['technician_id'] ?: null;
    $row['service_time']        = $_POST['service_time'] ?? null;
    $row['labor_cost']          = is_numeric(str_replace(',', '', $_POST['labor_cost'] ?? '')) ? (int)str_replace(',', '', $_POST['labor_cost']) : 0;
    $row['extra_costs']         = is_numeric(str_replace(',', '', $_POST['extra_costs'] ?? '')) ? (int)str_replace(',', '', $_POST['extra_costs']) : 0;
    $row['payment_status']      = in_array($_POST['payment_status'] ?? '', ['paid','unpaid','partial']) ? $_POST['payment_status'] : 'unpaid';
    $row['final_status']        = $_POST['final_status'] ?? '';
    $row['solution_description'] = $_POST['solution_description'] ?? '';
    $row['technician_notes']    = $_POST['technician_notes'] ?? '';
    $row['warranty_months']     = $_POST['warranty_months'] !== '' ? (int)$_POST['warranty_months'] : null;
    $row['updated_by']          = $_SESSION['user_id'];

    // تبدیل تاریخ‌ها
    $service_date = null;
    if (!empty($_POST['service_date'])) {
        $parts = explode('/', $_POST['service_date']);
        if (count($parts) == 3) {
            $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
            $service_date = sprintf("%04d-%02d-%02d", $g[0], $g[1], $g[2]);
        }
    }
    $row['service_date'] = $service_date;

    $next_service_date = null;
    if (!empty($_POST['next_service_date'])) {
        $parts = explode('/', $_POST['next_service_date']);
        if (count($parts) == 3) {
            $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
            $next_service_date = sprintf("%04d-%02d-%02d", $g[0], $g[1], $g[2]);
        }
    }
    $row['next_service_date'] = $next_service_date;

    try {
        $pdo->beginTransaction();

        // ذخیره نسخهٔ قبلی در لاگ (با ستون‌های اصلی)
        log_activity($pdo, $_SESSION['user_id'], 'update', 'repair', $id, json_encode($oldData, JSON_UNESCAPED_UNICODE));

        // حذف رکورد فعلی
        $pdo->prepare("DELETE FROM repairs WHERE id = ?")->execute([$id]);

        // درج دوباره با همان ID
        $columns = array_keys($row);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        $sql = "INSERT INTO repairs (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $placeholders) . ")";

        $stmt = $pdo->prepare($sql);
        foreach ($row as $col => $value) {

    if ($value === null) {
        $stmt->bindValue(":$col", null, PDO::PARAM_NULL);

    } elseif (is_int($value)) {
        $stmt->bindValue(":$col", $value, PDO::PARAM_INT);

    } else {
        $stmt->bindValue(":$col", $value, PDO::PARAM_STR);
    }
}
        $stmt->execute();

        // قطعات
        $pdo->prepare("DELETE FROM repair_parts WHERE repair_id = ?")->execute([$id]);
        if (!empty($_POST['parts'])) {
            $partsData = json_decode($_POST['parts'], true);
            if (is_array($partsData)) {
                $partStmt = $pdo->prepare("INSERT INTO repair_parts (repair_id, part_id, quantity, unit_price, purchase_price) VALUES (?,?,?,?,?)");
                foreach ($partsData as $part) {
                    if (empty($part['part_id'])) continue;
                    $partStmt->execute([$id, $part['part_id'], $part['quantity'] ?? 1, $part['unit_price'] ?? 0, $part['purchase_price'] ?? 0]);
                }
            }
        }

        $pdo->commit();
        $message = '<div class="alert alert-success">✅ تغییرات با موفقیت ذخیره شد.</div>';

        // بازخوانی داده‌ها
        $stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = '<div class="alert alert-error">❌ خطا: ' . $e->getMessage() . '</div>';
    }
}

// ---------- 3. لیست‌های کمکی ----------
$customers    = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
$deviceTypes  = $pdo->query("SELECT id, name FROM device_types ORDER BY name")->fetchAll();
$serviceTypes = $pdo->query("SELECT id, name FROM service_types ORDER BY name")->fetchAll();
$technicians  = $pdo->query("SELECT id, name FROM technicians ORDER BY name")->fetchAll();
$parts        = $pdo->query("SELECT id, name FROM parts ORDER BY name")->fetchAll();

$repairParts = $pdo->prepare("SELECT rp.*, p.name as part_name FROM repair_parts rp JOIN parts p ON rp.part_id = p.id WHERE rp.repair_id = ?");
$repairParts->execute([$id]);
$existingParts = $repairParts->fetchAll(PDO::FETCH_ASSOC);

// اطلاعات نمایشی (نام مشتری و ...)
$display = $pdo->prepare("SELECT c.name AS customer_name, c.phone AS customer_phone,
       t.name AS technician_name, dt.name AS device_type_name, st.name AS service_type_name
FROM repairs r
LEFT JOIN customers c ON r.customer_id = c.id
LEFT JOIN technicians t ON r.technician_id = t.id
LEFT JOIN device_types dt ON r.device_type_id = dt.id
LEFT JOIN service_types st ON r.service_type_id = st.id
WHERE r.id = ?");
$display->execute([$id]);
$display = $display->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>
<!-- استایل‌های اختصاصی این صفحه -->
<style>
    .page-title {
        font-size: 28px; font-weight: 800; color: #1e3a8a; margin-bottom: 20px;
    }
    .edit-card {
        background: #fff; border-radius: 22px; padding: 25px;
        box-shadow: 0 3px 18px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;
        margin-bottom: 25px;
    }
    .alert {
        padding: 15px 20px; border-radius: 14px; margin-bottom: 20px; font-weight: 600;
    }
    .alert-success { background: #dcfce7; color: #166534; }
    .alert-error { background: #fee2e2; color: #991b1b; }

    /* تب‌ها */
    .tab-nav {
        display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; gap: 5px;
    }
    .tab-btn {
        background: #f8fafc; border: 1px solid #e2e8f0; border-bottom: none;
        padding: 12px 24px; border-radius: 12px 12px 0 0; cursor: pointer;
        font-weight: 700; color: #475569; transition: 0.2s; font-family: inherit;
    }
    .tab-btn.active {
        background: #fff; color: #1e3a8a; border-color: #e2e8f0;
        border-bottom: 2px solid #fff; margin-bottom: -2px;
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* فیلدست‌ها */
    fieldset {
        border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 18px;
        background: #fafbff;
    }
    legend {
        font-weight: 700; font-size: 1.2rem; color: #1e3a8a;
        background: #fff; padding: 0 14px; border-radius: 20px;
        border: 1px solid #e2e8f0;
    }
    .form-row {
        display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 12px; align-items: center;
    }
    .form-row label {
        min-width: 80px; font-weight: 600; color: #334155;
    }
    .form-row input, .form-row select, .form-row textarea {
        flex: 1; padding: 12px 16px; border: 1px solid #ccd4dd; border-radius: 12px;
        font-size: 14px; background: #fff; font-family: inherit;
    }
    .form-row textarea { min-height: 90px; resize: vertical; }
    .form-row input:focus, .form-row select:focus, .form-row textarea:focus {
        border-color: #1e3c72; outline: none; box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
    }

    /* دکمه‌ها */
    .btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 12px 28px; border-radius: 40px; font-weight: 700; font-size: 15px;
        cursor: pointer; transition: 0.2s; text-decoration: none; border: none;
    }
    .btn-primary { background: #1e3c72; color: #fff; }
    .btn-primary:hover { background: #172d56; }
    .btn-secondary { background: #e2e8f0; color: #1e3c72; }
    .btn-secondary:hover { background: #cbd5e1; }
    .btn-small { padding: 8px 16px; font-size: 13px; border-radius: 30px; }

    /* قطعات */
    .parts-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
    .parts-table th { background: #f1f5f9; padding: 12px; text-align: right; font-weight: 700; color: #1e3a8a; border-bottom: 2px solid #e2e8f0; }
    .parts-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
    .parts-summary { text-align: left; font-weight: 700; margin: 10px 0; }
</style>

<h2 class="page-title">✏️ ویرایش سرویس</h2>

<div class="edit-card">
    <?= $message ?>
    <form method="POST" id="editForm">
        <!-- نوار تب‌ها -->
        <div class="tab-nav">
            <button type="button" class="tab-btn active" data-tab="customer">👤 مشتری</button>
            <button type="button" class="tab-btn" data-tab="device">🚗 دستگاه</button>
            <button type="button" class="tab-btn" data-tab="service">🛠️ خدمت</button>
            <button type="button" class="tab-btn" data-tab="schedule">📅 زمان‌بندی</button>
            <button type="button" class="tab-btn" data-tab="financial">💰 مالی</button>
            <button type="button" class="tab-btn" data-tab="result">📝 نتیجه</button>
        </div>

        <!-- محتوای تب‌ها -->
        <div class="tab-panel active" id="tab-customer">
            <fieldset>
                <legend>👤 مشتری</legend>
                <div class="form-row">
                    <label>مشتری</label>
                    <select name="customer_id" id="customer_select" required style="flex:2;">
                        <option value="">انتخاب کنید</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $row['customer_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-small" onclick="addNewItem('customer')">+ مشتری جدید</button>
                </div>
            </fieldset>
        </div>

        <div class="tab-panel" id="tab-device">
            <fieldset>
                <legend>🚗 دستگاه</legend>
                <div class="form-row">
                    <label>نوع دستگاه</label>
                    <select name="device_type_id" id="device_type_select" style="flex:2;">
                        <option value="">انتخاب کنید</option>
                        <?php foreach ($deviceTypes as $dt): ?>
                            <option value="<?= $dt['id'] ?>" <?= $row['device_type_id'] == $dt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-small" onclick="addNewItem('device_type')">+ نوع جدید</button>
                </div>
                <div class="form-row">
                    <label>برند</label><input type="text" name="device_brand" value="<?= htmlspecialchars($row['device_brand'] ?? '') ?>">
                    <label>مدل</label><input type="text" name="device_model" value="<?= htmlspecialchars($row['device_model'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <label>سریال</label><input type="text" name="device_serial" value="<?= htmlspecialchars($row['device_serial'] ?? '') ?>">
                    <label>سال نصب</label><input type="number" name="device_year" value="<?= htmlspecialchars($row['device_year'] ?? '') ?>" style="width:120px;">
                </div>
                <div class="form-row">
                    <label>محل نصب</label><input type="text" name="device_location" value="<?= htmlspecialchars($row['device_location'] ?? '') ?>" style="flex:1;">
                </div>
            </fieldset>
        </div>

        <div class="tab-panel" id="tab-service">
            <fieldset>
                <legend>🛠️ خدمت</legend>
                <div class="form-row">
                    <label>نوع خدمت</label>
                    <select name="service_type_id" id="service_type_select" style="flex:2;">
                        <?php foreach ($serviceTypes as $st): ?>
                            <option value="<?= $st['id'] ?>" <?= $row['service_type_id'] == $st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-small" onclick="addNewItem('service_type')">+ خدمت جدید</button>
                </div>
                <div class="form-row">
                    <label>قسمت مشکل‌دار</label>
                    <input type="text" name="problem_part" value="<?= htmlspecialchars($row['problem_part'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <label>شرح مشکل</label>
                    <textarea name="fault_description" required><?= htmlspecialchars($row['fault_description'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <label>اقدامات</label>
                    <textarea name="solution_description"><?= htmlspecialchars($row['solution_description'] ?? '') ?></textarea>
                </div>
            </fieldset>
        </div>

        <div class="tab-panel" id="tab-schedule">
            <fieldset>
                <legend>📅 زمان‌بندی</legend>
                <div class="form-row">
                    <label>تکنسین</label>
                    <select name="technician_id" id="technician_select" style="flex:2;">
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $row['technician_id'] == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-small" onclick="addNewItem('technician')">+ تکنسین جدید</button>
                </div>
                <div class="form-row">
                    <div style="flex:1"><label>تاریخ سرویس</label><div class="datepicker-wrapper"><input type="text" class="datepicker-input" readonly value="<?= $row['service_date'] ? get_jalali_date_str(strtotime($row['service_date'])) : '' ?>"><input type="hidden" name="service_date" id="service_date_input" value="<?= $row['service_date'] ? get_jalali_date_str(strtotime($row['service_date'])) : '' ?>"></div></div>
                    <div style="flex:0.5"><label>زمان</label><input type="time" name="service_time" value="<?= $row['service_time'] ?? '' ?>"></div>
                    <div style="flex:1"><label>سرویس بعدی</label><div class="datepicker-wrapper"><input type="text" class="datepicker-input" readonly value="<?= $row['next_service_date'] ? get_jalali_date_str(strtotime($row['next_service_date'])) : '' ?>"><input type="hidden" name="next_service_date" id="next_service_date_input" value="<?= $row['next_service_date'] ? get_jalali_date_str(strtotime($row['next_service_date'])) : '' ?>"></div></div>
                </div>
            </fieldset>
        </div>

        <div class="tab-panel" id="tab-financial">
            <fieldset>
                <legend>💰 مالی و قطعات</legend>
                <div class="form-row">
                    <label>مزد دست (ریال)</label>
                    <input type="text" name="labor_cost" id="labor_cost" value="<?= number_format($row['labor_cost']) ?>" oninput="formatInput(this)">
                </div>
                <div class="form-row">
                    <label>هزینه جانبی (ریال)</label>
                    <input type="text" name="extra_costs" id="extra_costs" value="<?= number_format($row['extra_costs']) ?>" oninput="formatInput(this)">
                </div>
                <div class="form-row">
                    <label>وضعیت پرداخت</label>
                    <select name="payment_status">
                        <option value="unpaid" <?= $row['payment_status'] == 'unpaid' ? 'selected' : '' ?>>تسویه نشده</option>
                        <option value="paid" <?= $row['payment_status'] == 'paid' ? 'selected' : '' ?>>پرداخت شده</option>
                        <option value="partial" <?= $row['payment_status'] == 'partial' ? 'selected' : '' ?>>نصفه</option>
                    </select>
                </div>
                <hr style="margin:15px 0;">
                <h4>قطعات مصرفی</h4>
                <table class="parts-table" id="partsTable">
                    <thead><tr><th>قطعه</th><th>تعداد</th><th>قیمت فروش</th><th>قیمت خرید</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
                <div class="parts-summary">مجموع فروش قطعات: <span id="partsTotal">0</span> ریال</div>
                <button type="button" class="btn btn-small" onclick="addPartRow()">➕ افزودن قطعه</button>
                <input type="hidden" name="parts" id="partsInput">
            </fieldset>
        </div>

        <div class="tab-panel" id="tab-result">
            <fieldset>
                <legend>📝 نتیجه</legend>
                <div class="form-row">
                    <label>وضعیت نهایی</label>
                    <input type="text" name="final_status" value="<?= htmlspecialchars($row['final_status'] ?? '') ?>">
                    <label>ماه‌های گارانتی</label>
                    <input type="number" name="warranty_months" value="<?= htmlspecialchars($row['warranty_months'] ?? '') ?>" style="width:120px;">
                </div>
                <div class="form-row">
                    <label>یادداشت تکنسین</label>
                    <textarea name="technician_notes"><?= htmlspecialchars($row['technician_notes'] ?? '') ?></textarea>
                </div>
            </fieldset>
        </div>

        <div style="text-align:center; margin-top:25px;">
            <button type="submit" class="btn btn-primary">💾 ذخیره تغییرات</button>
            <a href="index.php" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

<!-- مودال افزودن سریع -->
<div id="modal-add-item" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal('add-item')">&times;</span>
        <h3 id="add-item-title">افزودن جدید</h3>
        <div id="add-item-fields"></div>
        <div class="modal-actions">
            <button class="btn btn-primary" id="save-new-item">💾 ثبت</button>
            <button class="btn btn-secondary" onclick="closeModal('add-item')">انصراف</button>
        </div>
    </div>
</div>

<!-- قالب مخفی قطعات -->
<select id="partsSelectTemplate" style="display:none;">
    <option value="">انتخاب قطعه</option>
    <?php foreach ($parts as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
    <?php endforeach; ?>
</select>

<!-- اسکریپت‌های محلی -->
<script>
const existingParts = <?= json_encode($existingParts) ?>;
window.addEventListener('DOMContentLoaded', function() {
    if (existingParts.length > 0) {
        existingParts.forEach(p => addPartRow(p.part_id, p.quantity, p.unit_price, p.purchase_price));
    }

    // مدیریت تب‌ها
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById('tab-' + this.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    // جمع‌آوری قطعات قبل از ارسال
    document.getElementById('editForm').addEventListener('submit', function(e) {
        let parts = [];
        document.querySelectorAll('#partsTable tbody tr').forEach(row => {
            const s = row.querySelector('select');
            const q = row.querySelector('.part-qty');
            const p = row.querySelector('.part-price');
            const pp = row.querySelector('.part-purchase');
            if (s && s.value !== '' && q && p) {
                parts.push({
                    part_id: s.value,
                    quantity: parseInt(q.value) || 1,
                    unit_price: parseNumber(p.value),
                    purchase_price: pp ? parseNumber(pp.value) : 0
                });
            }
        });
        document.getElementById('partsInput').value = JSON.stringify(parts);
    });
});
</script>

<script src="script.js"></script>
</main>
</body>
</html>