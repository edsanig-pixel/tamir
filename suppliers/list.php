<?php
require_once __DIR__ . '../includes/config.php';
require_login($pdo);

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
require_once __DIR__ . '../includes/header.php';
?>
<style>
    .supplier-card { background: #fff; border-radius: 18px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid #e5e7eb; margin-bottom: 15px; position: relative; }
    .supplier-card h3 { color: #1e3a8a; margin-bottom: 8px; }
    .card-actions { position: absolute; top: 15px; left: 15px; display: flex; gap: 6px; }
</style>

<h2>🏭 مدیریت تأمین‌کنندگان</h2>
<button class="btn btn-primary" onclick="openSupplierModal()">➕ تأمین‌کننده جدید</button>

<div style="margin-top:20px;">
    <?php foreach ($suppliers as $s): ?>
        <div class="supplier-card">
            <div class="card-actions">
                <button class="btn small" onclick="editSupplier(<?= htmlspecialchars(json_encode($s)) ?>)">✏️</button>
                <button class="btn small danger" onclick="if(confirm('حذف شود؟')) window.location.href='?delete_id=<?= $s['id'] ?>'">🗑️</button>
            </div>
            <h3><?= htmlspecialchars($s['name']) ?></h3>
            <p>📞 <?= htmlspecialchars($s['phone'] ?? '—') ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- مودال -->
<div id="supplierModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeSupplierModal()">&times;</span>
        <h3 id="supplierModalTitle">افزودن تأمین‌کننده</h3>
        <form id="supplierForm">
            <input type="hidden" id="supId">
            <div class="form-row"><input type="text" id="supName" placeholder="نام *" required></div>
            <div class="form-row"><input type="text" id="supPhone" placeholder="تلفن"></div>
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeSupplierModal()">انصراف</button>
                <button type="submit" class="btn primary">ذخیره</button>
            </div>
        </form>
    </div>
</div>