<?php
require_once __DIR__ . '../includes/config.php';
require_login($pdo);

$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
$parts     = $pdo->query("SELECT id, name FROM parts ORDER BY name")->fetchAll();
require_once __DIR__ . '../includes/header.php';
?>
<style>
    .purchase-form { background: #fff; border-radius: 22px; padding: 25px; box-shadow: 0 3px 18px rgba(0,0,0,0.05); }
    .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    .items-table th { background: #f1f5f9; padding: 12px; }
    .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
</style>

<h2>📦 ثبت فاکتور خرید</h2>
<div class="purchase-form">
    <form id="purchaseForm">
        <div class="form-row">
            <select id="purchaseSupplier" required><option value="">تأمین‌کننده</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="purchaseInvoice" placeholder="شماره فاکتور">
            <input type="text" id="purchaseDate" class="datepicker-input" readonly placeholder="تاریخ">
            <input type="hidden" id="purchaseDateHidden">
        </div>
        <table class="items-table" id="purchaseItemsTable">
            <thead><tr><th>قطعه</th><th>تعداد</th><th>قیمت واحد</th><th></th></tr></thead>
            <tbody></tbody>
        </table>
        <button type="button" class="btn small" onclick="addPurchaseItemRow()">➕ افزودن قطعه</button>
        <div class="form-row" style="margin-top:15px;">
            <textarea id="purchaseNotes" placeholder="توضیحات"></textarea>
        </div>
        <div class="modal-actions">
            <button type="submit" class="btn primary">💾 ثبت خرید</button>
        </div>
    </form>
</div>

<select id="purchasePartsTemplate" style="display:none;">
    <option value="">انتخاب قطعه</option>
    <?php foreach ($parts as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
    <?php endforeach; ?>
</select>