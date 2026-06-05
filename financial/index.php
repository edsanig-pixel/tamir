<?php
require_once __DIR__ . '/../includes/config.php';
require_login($pdo);

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

// جستجو
if ($search !== '') {
    $where[] = "(
        r.invoice_number LIKE ?
        OR c.name LIKE ?
        OR c.phone LIKE ?
        OR r.device_brand LIKE ?
        OR r.device_model LIKE ?
    )";

    for ($i=0; $i<5; $i++) {
        $params[] = "%{$search}%";
    }
}

// وضعیت پرداخت
if ($status !== '') {
    $where[] = "r.payment_status = ?";
    $params[] = $status;
}

$whereSql = '';
if ($where) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// لیست سرویس‌ها
$stmt = $pdo->prepare("
    SELECT
        r.*,
        c.name AS customer_name,
        c.phone AS customer_phone,
        dt.name AS device_type,
        st.name AS service_type,

        (
            SELECT COALESCE(SUM(quantity * unit_price),0)
            FROM repair_parts
            WHERE repair_id = r.id
        ) AS parts_total

    FROM repairs r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN device_types dt ON r.device_type_id = dt.id
    LEFT JOIN service_types st ON r.service_type_id = st.id

    $whereSql

    ORDER BY r.id DESC
");

$stmt->execute($params);
$repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>



<div class="page-header">

    <div>
        <div class="page-title">📋 لیست سرویس‌ها</div>
    </div>

    <a href="<?= BASE_URL ?>/repairs/create" class="btn primary">
        ➕ ثبت سرویس جدید
    </a>

</div>

<div class="filters-card">

    <form method="GET" class="filters-form">

        <input
            type="text"
            name="search"
            placeholder="جستجو شماره فاکتور، مشتری، موبایل..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <select name="status">
            <option value="">همه وضعیت‌ها</option>

            <option value="paid" <?= $status=='paid'?'selected':'' ?>>
                پرداخت شده
            </option>

            <option value="unpaid" <?= $status=='unpaid'?'selected':'' ?>>
                پرداخت نشده
            </option>

            <option value="partial" <?= $status=='partial'?'selected':'' ?>>
                پرداخت ناقص
            </option>
        </select>

        <button class="btn primary">
            🔍 جستجو
        </button>

        <a href="<?= BASE_URL ?>/financial" class="btn secondary">
            ↺ پاکسازی
        </a>

    </form>

</div>

<?php if(count($repairs)): ?>

<div class="service-grid">

<?php foreach($repairs as $r):

    $partsTotal = (int)$r['parts_total'];
    $grandTotal = $partsTotal + $r['labor_cost'] + $r['extra_costs'];

    switch($r['payment_status']){

        case 'paid':
            $paymentText = 'پرداخت شده';
            $paymentClass = 'badge-paid';
        break;

        case 'partial':
            $paymentText = 'پرداخت ناقص';
            $paymentClass = 'badge-partial';
        break;

        default:
            $paymentText = 'پرداخت نشده';
            $paymentClass = 'badge-unpaid';
        break;
    }

?>

<div class="service-card">

    <div class="service-top">

        <div>
            <div class="invoice-number">
                <?= htmlspecialchars($r['invoice_number']) ?>
            </div>

            <div class="service-date">
                <?= $r['service_date']
                    ? get_jalali_date_str(strtotime($r['service_date']))
                    : '---'
                ?>
            </div>
        </div>

        <div class="payment-badge <?= $paymentClass ?>">
            <?= $paymentText ?>
        </div>

    </div>

    <div class="info-list">

        <div class="info-box">
            <div class="info-label">مشتری</div>
            <div class="info-value">
    <a href="customer_profile.php?id=<?= $r['customer_id'] ?>" style="color:#1e3a8a; text-decoration:none; font-weight:700;">
        <?= htmlspecialchars($r['customer_name']) ?>
    </a>
</div>
        </div>

        <div class="info-box">
            <div class="info-label">تلفن</div>
            <div class="info-value">
                <?= htmlspecialchars($r['customer_phone']) ?>
            </div>
        </div>

        <div class="info-box">
            <div class="info-label">دستگاه</div>
            <div class="info-value">
                <?= htmlspecialchars($r['device_brand']) ?>
            </div>
        </div>

        <div class="info-box">
            <div class="info-label">نوع سرویس</div>
            <div class="info-value">
                <?= htmlspecialchars($r['service_type'] ?? '---') ?>
            </div>
        </div>

    </div>

    <div class="amounts">

        <div class="amount-card bg-blue">
            <div class="amount-title">مزد + هزینه</div>
            <div class="amount-value">
                <?= format_rial($r['labor_cost'] + $r['extra_costs']) ?>
            </div>
        </div>

        <div class="amount-card bg-green">
            <div class="amount-title">قطعات</div>
            <div class="amount-value">
                <?= format_rial($partsTotal) ?>
            </div>
        </div>

    </div>

    <div class="total-box">

        <div class="total-label">
            مبلغ کل فاکتور
        </div>

        <div class="total-value">
            <?= format_rial($grandTotal) ?>
        </div>

    </div>

    <!-- دکمه‌ها -->
<div class="actions">

    <button
        class="btn-action btn-view"
        onclick="openDetailsModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>, <?= $partsTotal ?>, <?= $grandTotal ?>)"
    >
        👁 مشاهده کامل
    </button>

    <a
        href="invoice.php?id=<?= $r['id'] ?>"
        class="btn-action btn-view"
    >
        🧾 فاکتور
    </a>

    <a
        href="edit.php?id=<?= $r['id'] ?>"
        class="btn-action btn-edit"
    >
        ✏️ ویرایش
    </a>

    <a
        href="delete.php?id=<?= $r['id'] ?>"
        class="btn-action btn-delete"
        onclick="return confirm('آیا از حذف این سرویس مطمئن هستید؟')"
    >
        🗑 حذف
    </a>

</div>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="empty-box">
    هیچ سرویسی پیدا نشد.
</div>

<?php endif; ?>

</main>
<!-- ================= مودال مشاهده کامل ================= -->

<div class="details-modal" id="detailsModal">

    <div class="details-box">

        <button class="details-close" onclick="closeDetailsModal()">
            ✕
        </button>

        <div class="details-header">

            <div>
                <div class="details-title" id="modalInvoice"></div>
            </div>

            <div id="modalStatus"></div>

        </div>

        <div class="details-grid">

            <div class="details-item">
                <div class="details-label">مشتری</div>
                <div class="details-value" id="modalCustomer"></div>
            </div>

            <div class="details-item">
                <div class="details-label">شماره تماس</div>
                <div class="details-value" id="modalPhone"></div>
            </div>

            <div class="details-item">
                <div class="details-label">نوع سرویس</div>
                <div class="details-value" id="modalService"></div>
            </div>

            <div class="details-item">
                <div class="details-label">برند دستگاه</div>
                <div class="details-value" id="modalBrand"></div>
            </div>

            <div class="details-item">
                <div class="details-label">مدل دستگاه</div>
                <div class="details-value" id="modalModel"></div>
            </div>

            <div class="details-item">
                <div class="details-label">سریال دستگاه</div>
                <div class="details-value" id="modalSerial"></div>
            </div>

            <div class="details-item">
                <div class="details-label">محل نصب</div>
                <div class="details-value" id="modalLocation"></div>
            </div>

            <div class="details-item">
                <div class="details-label">تاریخ سرویس</div>
                <div class="details-value" id="modalDate"></div>
            </div>

            <div class="details-item">
                <div class="details-label">گارانتی</div>
                <div class="details-value" id="modalWarranty"></div>
            </div>

        </div>

        <div class="details-section">

            <div class="details-section-title">
                شرح مشکل
            </div>

            <div class="details-text" id="modalFault"></div>

        </div>

        <div class="details-section">

            <div class="details-section-title">
                اقدامات انجام شده
            </div>

            <div class="details-text" id="modalSolution"></div>

        </div>

        <div class="details-section">

            <div class="details-section-title">
                توضیحات تکنسین
            </div>

            <div class="details-text" id="modalNotes"></div>

        </div>

        <div class="details-total">

            <div class="details-total-grid">

                <div class="total-mini">
                    <div class="total-mini-title">اجرت</div>
                    <div class="total-mini-value" id="modalLabor"></div>
                </div>

                <div class="total-mini">
                    <div class="total-mini-title">هزینه جانبی</div>
                    <div class="total-mini-value" id="modalExtra"></div>
                </div>

                <div class="total-mini">
                    <div class="total-mini-title">قطعات</div>
                    <div class="total-mini-value" id="modalParts"></div>
                </div>

                <div class="total-mini">
                    <div class="total-mini-title">وضعیت</div>
                    <div class="total-mini-value" id="modalPayText"></div>
                </div>

            </div>

            <div class="final-grand-total" id="modalGrand"></div>

        </div>

    </div>

</div>

<script>

function moneyFormat(num){

    return new Intl.NumberFormat('fa-IR').format(num) + ' ریال';

}

function openDetailsModal(data, partsTotal, grandTotal){

    document.getElementById('detailsModal').classList.add('active');

    document.getElementById('modalInvoice').innerText =
        'فاکتور ' + (data.invoice_number || '-');

    let statusClass = 'status-unpaid';
    let statusText = 'پرداخت نشده';

    if(data.payment_status === 'paid'){
        statusClass = 'status-paid';
        statusText = 'پرداخت شده';
    }

    if(data.payment_status === 'partial'){
        statusClass = 'status-partial';
        statusText = 'پرداخت ناقص';
    }

    document.getElementById('modalStatus').innerHTML =
        `<div class="details-status ${statusClass}">
            ${statusText}
        </div>`;

    document.getElementById('modalCustomer').innerText =
        data.customer_name || '-';

    document.getElementById('modalPhone').innerText =
        data.customer_phone || '-';

    document.getElementById('modalService').innerText =
        data.service_type || '-';

    document.getElementById('modalBrand').innerText =
        data.device_brand || '-';

    document.getElementById('modalModel').innerText =
        data.device_model || '-';

    document.getElementById('modalSerial').innerText =
        data.device_serial || '-';

    document.getElementById('modalLocation').innerText =
        data.device_location || '-';

    document.getElementById('modalDate').innerText =
        data.service_date || '-';

    document.getElementById('modalWarranty').innerText =
        (data.warranty_months || 0) + ' ماه';

    document.getElementById('modalFault').innerText =
        data.fault_description || '-';

    document.getElementById('modalSolution').innerText =
        data.solution_description || '-';

    document.getElementById('modalNotes').innerText =
        data.technician_notes || '-';

    document.getElementById('modalLabor').innerText =
        moneyFormat(data.labor_cost || 0);

    document.getElementById('modalExtra').innerText =
        moneyFormat(data.extra_costs || 0);

    document.getElementById('modalParts').innerText =
        moneyFormat(partsTotal || 0);

    document.getElementById('modalPayText').innerText =
        statusText;

    document.getElementById('modalGrand').innerText =
        'جمع کل: ' + moneyFormat(grandTotal || 0);

}

function closeDetailsModal(){

    document.getElementById('detailsModal').classList.remove('active');

}

window.addEventListener('click', function(e){

    let modal = document.getElementById('detailsModal');

    if(e.target === modal){
        closeDetailsModal();
    }

});

</script>
</body>
</html>