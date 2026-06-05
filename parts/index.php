<?php
require_once __DIR__ . '/../includes/config.php';
require_login($pdo);

$message = '';
$editPart = null;

// حذف قطعه
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $stmt = $pdo->prepare("
    UPDATE parts
    SET is_active = 0
    WHERE id = ?
");
$stmt->execute([$_GET['delete_id']]);
    log_activity($pdo, $_SESSION['user_id'], 'delete', 'part', $_GET['delete_id'], 'حذف قطعه');
    header("Location: parts.php?msg=deleted");
    exit;
}

// بارگذاری برای ویرایش
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM parts WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $editPart = $stmt->fetch();
}

// ذخیره (ایجاد یا ویرایش)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $defPurchase = $_POST['default_purchase_price'] !== '' ? (int)str_replace(',', '', $_POST['default_purchase_price']) : null;
    $defSale = $_POST['default_sale_price'] !== '' ? (int)str_replace(',', '', $_POST['default_sale_price']) : null;

    if ($name === '') {
        $message = '<p class="error">نام قطعه الزامی است.</p>';
    } else {
        if (isset($_POST['part_id']) && is_numeric($_POST['part_id'])) {
            // ویرایش
            $stmt = $pdo->prepare("UPDATE parts SET name = ?, stock = ?, default_purchase_price = ?, default_sale_price = ? WHERE id = ?");
            $stmt->execute([$name, $stock, $defPurchase, $defSale, $_POST['part_id']]);
            log_activity($pdo, $_SESSION['user_id'], 'update', 'part', $_POST['part_id'], "ویرایش قطعه $name");
            $message = '<p class="success">✅ قطعه با موفقیت به‌روز شد.</p>';
            $editPart = null;
        } else {
            // ایجاد
            $stmt = $pdo->prepare("INSERT INTO parts (name, stock, default_purchase_price, default_sale_price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $stock, $defPurchase, $defSale]);
            log_activity($pdo, $_SESSION['user_id'], 'create', 'part', $pdo->lastInsertId(), "ایجاد قطعه $name");
            $message = '<p class="success">✅ قطعه جدید با موفقیت ثبت شد.</p>';
        }
    }
}

// دریافت لیست قطعات
$search = $_GET['search'] ?? '';
$where = '';
$params = [];
if (!empty($search)) {
    $where = " WHERE name LIKE ?";
    $params[] = "%$search%";
}
$sql = "SELECT * FROM parts WHERE is_active = 1";

if (!empty($search)) {
    $sql .= " AND name LIKE ?";
}

$sql .= " ORDER BY name";

$parts = $pdo->prepare($sql);
$parts->execute($params);
$parts = $parts->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2>⚙️ مدیریت قطعات و انبار</h2>

<div class="card">
    <?= $message ?>

    <!-- فرم افزودن / ویرایش -->
    <h3><?= $editPart ? '✏️ ویرایش قطعه' : '➕ افزودن قطعه جدید' ?></h3>
    <form method="POST" style="margin-bottom:20px;">
        <?php if ($editPart): ?>
            <input type="hidden" name="part_id" value="<?= $editPart['id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <input type="text" name="name" placeholder="نام قطعه *" required value="<?= htmlspecialchars($editPart['name'] ?? '') ?>" style="flex:2;">
            <input type="number" name="stock" placeholder="موجودی" value="<?= $editPart['stock'] ?? 0 ?>" style="flex:1;">
            <input type="text" name="default_purchase_price" placeholder="قیمت خرید (ریال)" oninput="formatInput(this)" value="<?= $editPart ? number_format($editPart['default_purchase_price']) : '' ?>" style="flex:1;">
            <input type="text" name="default_sale_price" placeholder="قیمت فروش (ریال)" oninput="formatInput(this)" value="<?= $editPart ? number_format($editPart['default_sale_price']) : '' ?>" style="flex:1;">
            <button type="submit" class="btn primary"><?= $editPart ? '💾 به‌روزرسانی' : '➕ ثبت قطعه' ?></button>
            <?php if ($editPart): ?>
                <a href="<?= BASE_URL ?>/parts" class="btn secondary">انصراف</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- جستجو -->
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="🔍 جستجوی قطعه..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn">جستجو</button>
        <?php if ($search): ?><a href="<?= BASE_URL ?>/parts" style="color:#e74c3c;">✖ حذف</a><?php endif; ?>
    </form>

    <!-- جدول قطعات -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>نام قطعه</th>
                <th>موجودی</th>
                <th>قیمت خرید (ریال)</th>
                <th>قیمت فروش (ریال)</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parts as $i => $part): ?>
                <?php $lowStock = $part['stock'] < 5; ?>
                <tr style="<?= $lowStock ? 'background:#fff5f5;' : '' ?>">
                    <td><?= $i + 1 ?></td>
                    <td>
                        <?= htmlspecialchars($part['name']) ?>
                        <?php if ($lowStock): ?>
                            <span style="color:#dc2626; font-size:12px; margin-right:5px;">⚠ کمبود</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:bold; color:<?= $lowStock ? '#dc2626' : '#333' ?>"><?= $part['stock'] ?></td>
                    <td><?= $part['default_purchase_price'] ? number_format($part['default_purchase_price']) : '—' ?></td>
                    <td><?= $part['default_sale_price'] ? number_format($part['default_sale_price']) : '—' ?></td>
                    <td>
                        <a href="?edit_id=<?= $part['id'] ?>" class="btn small warning">✏️</a>
                        <a href="?delete_id=<?= $part['id'] ?>" class="btn small danger" onclick="return confirm('آیا از حذف این قطعه مطمئن هستید؟')">🗑</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</main>
</body>
</html>