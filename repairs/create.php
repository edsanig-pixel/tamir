<?php require_once __DIR__.'/../includes/header.php';
require_login($pdo);
$customers    = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
$deviceTypes  = $pdo->query("SELECT id, name FROM device_types ORDER BY name")->fetchAll();
$serviceTypes = $pdo->query("SELECT id, name FROM service_types ORDER BY name")->fetchAll();
$technicians  = $pdo->query("SELECT id, name FROM technicians ORDER BY name")->fetchAll();
$parts        = $pdo->query("SELECT id, name FROM parts ORDER BY name")->fetchAll();
$invoice      = generate_invoice_number($pdo);
?>
<h2>➕ ثبت سرویس جدید</h2>
<div class="card" style="margin-bottom:20px;"><span>فاکتور: <strong><?=$invoice?></strong></span></div>

<div class="service-cards">
  <div class="s-card" onclick="openModal('customer')">👤 مشتری<br><small id="custInfo">تکمیل نشده</small></div>
  <div class="s-card" onclick="openModal('device')">🚗 دستگاه<br><small id="devInfo">تکمیل نشده</small></div>
  <div class="s-card" onclick="openModal('service')">🛠️ خدمت<br><small id="servInfo">تکمیل نشده</small></div>
  <div class="s-card" onclick="openModal('schedule')">📅 زمان‌بندی<br><small id="schInfo">تکمیل نشده</small></div>
  <div class="s-card" onclick="openModal('financial')">💰 مالی<br><small id="finInfo">تکمیل نشده</small></div>
  <div class="s-card" onclick="openModal('result')">📝 نتیجه<br><small id="resInfo">تکمیل نشده</small></div>
</div>
<div style="text-align:center; margin-top:20px;">
  <button type="button" class="btn primary" onclick="submitAll()">🚀 ثبت نهایی</button>
</div>

<!-- مودال مشتری -->
<div id="modal-customer" class="modal"><div class="modal-content">
  <span class="modal-close" onclick="closeModal('customer')">&times;</span><h3>👤 اطلاعات مشتری</h3>
  <label>مشتری</label>
  <div class="form-row"><select id="customer_select" required><option value="">انتخاب کنید</option><?php foreach($customers as $c):?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach?></select><button class="btn small" onclick="addNewItem('customer')">+ مشتری جدید</button></div>
  <div style="margin-top:20px;"><button class="btn primary" onclick="saveSection('customer')">💾 تأیید</button><button class="btn secondary" onclick="closeModal('customer')">بازگشت</button></div>
</div></div>

<!-- مودال دستگاه -->
<div id="modal-device" class="modal"><div class="modal-content">
  <span class="modal-close" onclick="closeModal('device')">&times;</span><h3>🚗 اطلاعات دستگاه</h3>
  <label>نوع دستگاه</label>
  <div class="form-row"><select id="device_type_select"><option value="">انتخاب کنید</option><?php foreach($deviceTypes as $dt):?><option value="<?=$dt['id']?>"><?=htmlspecialchars($dt['name'])?></option><?php endforeach?></select><button class="btn small" onclick="addNewItem('device_type')">+ دستگاه جدید</button></div>
  <div class="form-row"><label>برند</label><input type="text" id="device_brand"><label>مدل</label><input type="text" id="device_model"></div>
  <div class="form-row"><label>سریال</label><input type="text" id="device_serial"><label>سال نصب</label><input type="number" id="device_year" style="width:120px;"></div>
  <div class="form-row"><label>محل نصب</label><input type="text" id="device_location"></div>
  <div style="margin-top:20px;"><button class="btn primary" onclick="saveSection('device')">💾 تأیید</button><button class="btn secondary" onclick="closeModal('device')">بازگشت</button></div>
</div></div>

<!-- مودال خدمت -->
<div id="modal-service" class="modal"><div class="modal-content">
  <span class="modal-close" onclick="closeModal('service')">&times;</span><h3>🛠️ نوع خدمت و مشکل</h3>
  <div class="form-row"><label>نوع خدمت</label><select id="service_type_select"><option value="">انتخاب کنید</option><?php foreach($serviceTypes as $st):?><option value="<?=$st['id']?>"><?=htmlspecialchars($st['name'])?></option><?php endforeach?></select><button class="btn small" onclick="addNewItem('service_type')">+ خدمت جدید</button><label>قسمت مشکل‌دار</label><input type="text" id="problem_part"></div>
  <div class="form-row"><label>شرح مشکل</label><textarea id="fault_description" required></textarea><label>اقدامات</label><textarea id="solution_description"></textarea></div>
  <div style="margin-top:20px;"><button class="btn primary" onclick="saveSection('service')">💾 تأیید</button><button class="btn secondary" onclick="closeModal('service')">بازگشت</button></div>
</div></div>

<!-- مودال زمان‌بندی -->
<div id="modal-schedule" class="modal"><div class="modal-content">
  <span class="modal-close" onclick="closeModal('schedule')">&times;</span><h3>📅 زمان‌بندی</h3>
  <label>تکنسین</label>
  <div class="form-row"><select id="technician_select"><option value="">انتخاب کنید</option><?php foreach($technicians as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach?></select><button class="btn small" onclick="addNewItem('technician')">+ تکنسین جدید</button></div>
  <div class="form-row" style="gap:10px;">
    <div style="flex:1"><label>تاریخ سرویس</label><div class="datepicker-wrapper"><input type="text" class="datepicker-input" readonly><input type="hidden" id="service_date_input"></div></div>
    <div style="flex:0.5"><label>زمان</label><input type="time" id="service_time"></div>
    <div style="flex:1"><label>سرویس بعدی</label><div class="datepicker-wrapper"><input type="text" class="datepicker-input" readonly><input type="hidden" id="next_service_date_input"></div></div>
  </div>
  <div style="margin-top:20px;"><button class="btn primary" onclick="saveSection('schedule')">💾 تأیید</button><button class="btn secondary" onclick="closeModal('schedule')">بازگشت</button></div>
</div></div>

<!-- مودال مالی -->
<div id="modal-financial" class="modal"><div class="modal-content">
  <span class="modal-close" onclick="closeModal('financial')">&times;</span><h3>💰 مالی و قطعات</h3>
  <label>مزد دست (ریال)</label><input type="text" id="labor_cost" value="0" oninput="formatInput(this)">
  <label>هزینه‌های جانبی (ریال)</label><input type="text" id="extra_costs" value="0" oninput="formatInput(this)">
  <label>وضعیت پرداخت</label><select id="payment_status"><option value="unpaid">تسویه نشده</option><option value="paid">پرداخت شده</option><option value="partial">پیش پرداخت</option></select>
  <hr><h4>قطعات مصرفی</h4>
  <table id="partsTable"><thead><tr><th>قطعه</th><th>تعداد</th><th>قیمت فروش</th><th>قیمت خرید</th><th></th></tr></thead><tbody></tbody></table>
  <div class="parts-summary">مجموع فروش قطعات: <span id="partsTotal">0</span> ریال</div>
  <button type="button" class="btn small" onclick="addPartRow()">➕ افزودن قطعه</button>
  <div style="margin-top:20px;"><button class="btn primary" onclick="saveSection('financial')">💾 تأیید</button><button class="btn secondary" onclick="closeModal('financial')">بازگشت</button></div>
</div></div>

<!-- مودال نتیجه -->
<div id="modal-result" class="modal"><div class="modal-content">
  <span class="modal-close" onclick="closeModal('result')">&times;</span><h3>📝 نتیجه</h3>
  <div class="form-row"><label>وضعیت نهایی</label><input type="text" id="final_status"><label>ماه‌های گارانتی</label><input type="number" id="warranty_months"></div>
  <div class="form-row"><label>یادداشت تکنسین</label><textarea id="technician_notes"></textarea></div>
  <div style="margin-top:20px;"><button class="btn primary" onclick="saveSection('result')">💾 تأیید</button><button class="btn secondary" onclick="closeModal('result')">بازگشت</button></div>
</div></div>

<!-- مودال افزودن سریع -->
<div id="modal-add-item" class="modal"><div class="modal-content"><span class="modal-close" onclick="closeModal('add-item')">&times;</span><h3 id="add-item-title">افزودن جدید</h3><div id="add-item-fields"></div><div class="modal-actions"><button class="btn primary" id="save-new-item">💾 ثبت</button><button class="btn secondary" onclick="closeModal('add-item')">انصراف</button></div></div></div>

<select id="partsSelectTemplate" style="display:none">...</select>
</main></body></html>