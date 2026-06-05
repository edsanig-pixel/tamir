<?php
require_once __DIR__ . '/../includes/config.php';
require_login($pdo);

$transactions = $pdo->query("
    SELECT t.*, c.name AS customer_name, s.name AS supplier_name, r.invoice_number AS repair_invoice
    FROM transactions t
    LEFT JOIN customers c ON t.customer_id = c.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    LEFT JOIN repairs r ON t.related_repair_id = r.id
    ORDER BY t.date DESC, t.id DESC
    LIMIT 50
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h2>💰 گزارش دریافتی‌ها و پرداختی‌ها</h2>
<table>
    <thead><tr><th>تاریخ</th><th>نوع</th><th>مبلغ</th><th>مشتری / تأمین‌کننده</th><th>توضیح</th></tr></thead>
    <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= get_jalali_date_str(strtotime($t['date'])) ?></td>
                <td><?= $t['type'] === 'income' ? '✅ دریافت' : '❌ پرداخت' ?></td>
                <td><?= format_rial($t['amount']) ?></td>
                <td><?= htmlspecialchars($t['customer_name'] ?? $t['supplier_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>