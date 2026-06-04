<?php
require_once __DIR__ . '/config.php';
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

require_once __DIR__ . '/header.php';
?>

<style>

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    gap:15px;
    flex-wrap:wrap;
}

.page-title{
    font-size:28px;
    font-weight:800;
    color:#1e3a8a;
}

.filters-card{
    background:#fff;
    border-radius:18px;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 2px 15px rgba(0,0,0,.05);
    border:1px solid #e5e7eb;
}

.filters-form{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    align-items:center;
}

.filters-form input,
.filters-form select{
    height:48px;
    border-radius:12px;
}

.service-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(370px,1fr));
    gap:22px;
}

.service-card{
    background:#fff;
    border-radius:22px;
    padding:22px;
    box-shadow:0 3px 18px rgba(0,0,0,.05);
    border:1px solid #e5e7eb;
    position:relative;
    overflow:hidden;
    transition:.25s;
}

.service-card:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.service-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:18px;
    gap:10px;
}

.invoice-number{
    font-size:18px;
    font-weight:800;
    color:#1e3a8a;
}

.service-date{
    color:#6b7280;
    font-size:13px;
    margin-top:5px;
}

.payment-badge{
    padding:8px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
    white-space:nowrap;
}

.badge-paid{
    background:#dcfce7;
    color:#166534;
}

.badge-unpaid{
    background:#fee2e2;
    color:#991b1b;
}

.badge-partial{
    background:#fef3c7;
    color:#92400e;
}

.info-list{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-bottom:18px;
}

.info-box{
    background:#f8fafc;
    border-radius:14px;
    padding:12px;
    border:1px solid #e2e8f0;
}

.info-label{
    font-size:12px;
    color:#64748b;
    margin-bottom:5px;
}

.info-value{
    font-weight:700;
    color:#0f172a;
    line-height:1.8;
    word-break:break-word;
}

.amounts{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin-bottom:20px;
}

.amount-card{
    border-radius:14px;
    padding:14px;
    color:#fff;
}

.amount-title{
    font-size:12px;
    opacity:.9;
    margin-bottom:8px;
}

.amount-value{
    font-size:17px;
    font-weight:800;
}

.bg-blue{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
}

.bg-green{
    background:linear-gradient(135deg,#059669,#047857);
}

.total-box{
    background:#0f172a;
    color:#fff;
    border-radius:16px;
    padding:16px;
    margin-bottom:20px;
}

.total-label{
    font-size:13px;
    opacity:.8;
    margin-bottom:8px;
}

.total-value{
    font-size:22px;
    font-weight:900;
}

.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-action{
    flex:1;
    min-width:100px;
    text-align:center;
    padding:12px 14px;
    border-radius:12px;
    text-decoration:none;
    font-weight:700;
    transition:.2s;
    border:none;
    cursor:pointer;
    font-family:inherit;
}

.btn-view{
    background:#dbeafe;
    color:#1d4ed8;
}

.btn-edit{
    background:#fef3c7;
    color:#92400e;
}

.btn-delete{
    background:#fee2e2;
    color:#b91c1c;
}

.btn-action:hover{
    opacity:.9;
    transform:translateY(-1px);
}

.empty-box{
    background:#fff;
    border-radius:20px;
    padding:60px 20px;
    text-align:center;
    border:1px dashed #cbd5e1;
    color:#64748b;
    font-size:18px;
}

@media(max-width:768px){

    .service-grid{
        grid-template-columns:1fr;
    }

    .info-list,
    .amounts{
        grid-template-columns:1fr;
    }

}

/* ================= مودال مشاهده کامل ================= */

.details-modal{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.75);
    backdrop-filter:blur(6px);
    z-index:99999;
    display:none;
    align-items:center;
    justify-content:center;
    padding:20px;
}

.details-modal.active{
    display:flex;
    animation:fadeIn .2s ease;
}

.details-box{
    width:100%;
    max-width:1000px;
    max-height:92vh;
    overflow-y:auto;
    background:#fff;
    border-radius:28px;
    padding:28px;
    position:relative;
    box-shadow:0 30px 80px rgba(0,0,0,.35);
}

.details-close{
    position:absolute;
    top:18px;
    left:18px;
    width:42px;
    height:42px;
    border:none;
    border-radius:50%;
    background:#fee2e2;
    color:#b91c1c;
    cursor:pointer;
    font-size:18px;
    font-weight:bold;
}

.details-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    gap:15px;
    flex-wrap:wrap;
}

.details-title{
    font-size:28px;
    font-weight:900;
    color:#1e3a8a;
}

.details-status{
    padding:10px 18px;
    border-radius:40px;
    font-size:13px;
    font-weight:800;
}

.status-paid{
    background:#dcfce7;
    color:#166534;
}

.status-unpaid{
    background:#fee2e2;
    color:#991b1b;
}

.status-partial{
    background:#fef3c7;
    color:#92400e;
}

.details-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:20px;
}

.details-item{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:16px;
}

.details-label{
    font-size:12px;
    color:#64748b;
    margin-bottom:8px;
}

.details-value{
    font-size:15px;
    font-weight:700;
    color:#0f172a;
    line-height:1.9;
    word-break:break-word;
}

.details-section{
    margin-top:20px;
}

.details-section-title{
    font-size:18px;
    font-weight:800;
    color:#1e3a8a;
    margin-bottom:12px;
}

.details-text{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:18px;
    line-height:2.1;
    font-size:14px;
}

.details-total{
    margin-top:25px;
    background:linear-gradient(135deg,#1e3a8a,#1d4ed8);
    color:#fff;
    border-radius:22px;
    padding:25px;
}

.details-total-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:15px;
}

.total-mini{
    background:rgba(255,255,255,.1);
    border-radius:16px;
    padding:14px;
}

.total-mini-title{
    font-size:12px;
    opacity:.8;
    margin-bottom:8px;
}

.total-mini-value{
    font-size:16px;
    font-weight:800;
}

.final-grand-total{
    margin-top:20px;
    text-align:left;
    font-size:30px;
    font-weight:900;
}

@media(max-width:768px){

    .details-grid{
        grid-template-columns:1fr;
    }

    .details-total-grid{
        grid-template-columns:1fr 1fr;
    }

}

</style>

<div class="page-header">

    <div>
        <div class="page-title">📋 لیست سرویس‌ها</div>
    </div>

    <a href="create.php" class="btn primary">
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

        <a href="reports.php" class="btn secondary">
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