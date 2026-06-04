// ========== توابع تاریخ شمسی (آفلاین) ==========
function jGregorianToJalali(gy, gm, gd) {
    let g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    let gy2 = (gm > 2) ? (gy + 1) : gy;
    let days = 355666 + (365 * gy) + ~~((gy2 + 3) / 4) - ~~((gy2 + 99) / 100) + ~~((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
    let jy = -1595 + (33 * ~~(days / 12053));
    days %= 12053;
    jy += 4 * ~~(days / 1461);
    days %= 1461;
    if (days > 365) { jy += ~~((days - 1) / 365); days = (days - 1) % 365; }
    let jm = (days < 186) ? 1 + ~~(days / 31) : 7 + ~~((days - 186) / 30);
    let jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
    return [jy, jm, jd];
}
function jJalaliToGregorian(jy, jm, jd) {
    jy += 1595;
    let days = -355668 + 365 * jy + ~~(jy / 33) * 8 + ~~((jy % 33 + 3) / 4);
    if (jm < 7) days += (jm - 1) * 31;
    else days += ((jm - 7) * 30) + 186;
    days += jd;
    let gy = 400 * ~~(days / 146097);
    days %= 146097;
    if (days > 36524) { gy += 100 * ~~(--days / 36524); days %= 36524; if (days >= 365) days++; }
    gy += 4 * ~~(days / 1461);
    days %= 1461;
    if (days > 365) { gy += ~~((days - 1) / 365); days = (days - 1) % 365; }
    let gd2 = days + 1, sal_a = [0,31,(gy%4==0&&gy%100!=0)||(gy%400==0)?29:28,31,30,31,30,31,31,30,31,30,31];
    let gm = 1;
    while (gm < 13 && gd2 > sal_a[gm]) { gd2 -= sal_a[gm]; gm++; }
    return [gy, gm, gd2];
}
const persianMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
function jalaliLeap(y) { const a = (y - 474) % 2820 + 474; return ((a + 38) * 31) % 128 < 31; }
function jalaliDaysInMonth(y, m) { return m <= 6 ? 31 : m <= 11 ? 30 : jalaliLeap(y) ? 30 : 29; }

// ========== شیء ذخیره‌سازی موقت داده‌های سرویس ==========
const serviceData = {
    customer_id: null, device_type_id: null, device_brand: '', device_model: '', device_serial: '',
    device_year: '', device_location: '', service_type_id: null, problem_part: '', fault_description: '',
    technician_id: null, service_date: '', service_time: '', next_service_date: '',
    labor_cost: 0, extra_costs: 0, payment_status: 'unpaid', parts: [],
    final_status: '', solution_description: '', technician_notes: '', warranty_months: ''
};

// ========== توابع کمکی ==========
function formatInput(input) {
    let val = input.value.replace(/,/g, '').replace(/[^0-9]/g, '');
    input.value = val ? Number(val).toLocaleString('en-US') : '';
}
function parseNumber(str) { return parseInt(str.replace(/,/g, '')) || 0; }
function formatMoney(amount) { return Number(amount).toLocaleString('en-US') + ' ریال'; }

// ========== مدیریت مودال‌ها ==========
function openModal(type) {
    const modal = document.getElementById('modal-' + type);
    if (modal) modal.style.display = 'flex';
}
function closeModal(type) {
    const modal = document.getElementById('modal-' + type);
    if (modal) modal.style.display = 'none';
}

// ========== ذخیره‌سازی هر بخش (از مودال‌ها به serviceData) ==========
function saveSection(type) {
    switch (type) {
        case 'customer':
            const cs = document.getElementById('customer_select');
            serviceData.customer_id = cs?.value || null;
            document.getElementById('custInfo').textContent = cs?.selectedOptions[0]?.text || 'تکمیل نشده';
            break;
        case 'device':
            serviceData.device_type_id = document.getElementById('device_type_select')?.value || null;
            serviceData.device_brand = document.getElementById('device_brand')?.value || '';
            serviceData.device_model = document.getElementById('device_model')?.value || '';
            serviceData.device_serial = document.getElementById('device_serial')?.value || '';
            serviceData.device_year = document.getElementById('device_year')?.value || '';
            serviceData.device_location = document.getElementById('device_location')?.value || '';
            document.getElementById('devInfo').textContent = serviceData.device_brand || 'تکمیل نشده';
            break;
        case 'service':
            serviceData.service_type_id = document.getElementById('service_type_select')?.value || null;
            serviceData.problem_part = document.getElementById('problem_part')?.value || '';
            serviceData.fault_description = document.getElementById('fault_description')?.value || '';
            document.getElementById('servInfo').textContent = serviceData.fault_description ? 'شرح وارد شد' : 'تکمیل نشده';
            break;
        case 'schedule':
            serviceData.technician_id = document.getElementById('technician_select')?.value || null;
            serviceData.service_date = document.getElementById('service_date_input')?.value || '';
            serviceData.service_time = document.getElementById('service_time')?.value || '';
            serviceData.next_service_date = document.getElementById('next_service_date_input')?.value || '';
            document.getElementById('schInfo').textContent = serviceData.service_date || 'تکمیل نشده';
            break;
        case 'financial':
            serviceData.labor_cost = parseNumber(document.getElementById('labor_cost')?.value);
            serviceData.extra_costs = parseNumber(document.getElementById('extra_costs')?.value);
            serviceData.payment_status = document.getElementById('payment_status')?.value || 'unpaid';
            serviceData.parts = [];
            document.querySelectorAll('#partsTable tbody tr').forEach(row => {
                const s = row.querySelector('select'), q = row.querySelector('.part-qty'), p = row.querySelector('.part-price'), pp = row.querySelector('.part-purchase');
                if (s && s.value && q && p) {
                    serviceData.parts.push({ part_id: s.value, quantity: parseInt(q.value) || 1, unit_price: parseNumber(p.value), purchase_price: pp ? parseNumber(pp.value) : 0 });
                }
            });
            document.getElementById('finInfo').textContent = 'مزد: ' + formatMoney(serviceData.labor_cost) + ' | ' + serviceData.parts.length + ' قطعه';
            break;
        case 'result':
            serviceData.final_status = document.getElementById('final_status')?.value || '';
            serviceData.solution_description = document.getElementById('solution_description')?.value || '';
            serviceData.technician_notes = document.getElementById('technician_notes')?.value || '';
            serviceData.warranty_months = document.getElementById('warranty_months')?.value || '';
            document.getElementById('resInfo').textContent = serviceData.final_status || 'تکمیل نشده';
            break;
    }
    closeModal(type);
}

// ========== جدول پویای قطعات ==========
function addPartRow(partId='', qty=1, unitPrice=0, purchasePrice=0) {
    const tbody = document.querySelector('#partsTable tbody');
    if (!tbody) return;
    const row = tbody.insertRow();
    const selCell = row.insertCell();
    const select = document.createElement('select');
    const tpl = document.getElementById('partsSelectTemplate');
    if (tpl) select.innerHTML = tpl.innerHTML;
    select.value = partId;
    select.addEventListener('change', function() {
        const priceInput = row.querySelector('.part-price');
        const purchaseInput = row.querySelector('.part-purchase');
        if (this.value) fetchPartInfo(this.value, priceInput, purchaseInput);
    });
    selCell.appendChild(select);
    const addBtn = document.createElement('button'); addBtn.type='button'; addBtn.textContent='+'; addBtn.className='btn small'; addBtn.onclick=function(e){e.stopPropagation();addNewItem('part');};
    selCell.appendChild(addBtn);
    const qtyCell = row.insertCell();
    const qtyInput = document.createElement('input'); qtyInput.type='number'; qtyInput.value=qty; qtyInput.min=1; qtyInput.className='part-qty'; qtyInput.style.width='70px'; qtyInput.addEventListener('change',updatePartsTotal);
    qtyCell.appendChild(qtyInput);
    const priceCell = row.insertCell();
    const priceInput = document.createElement('input'); priceInput.type='text'; priceInput.value=unitPrice?unitPrice.toLocaleString('en-US'):''; priceInput.className='part-price'; priceInput.addEventListener('input',function(){formatInput(this);updatePartsTotal();});
    priceCell.appendChild(priceInput);
    const purchCell = row.insertCell();
    const purchInput = document.createElement('input'); purchInput.type='text'; purchInput.value=purchasePrice?purchasePrice.toLocaleString('en-US'):''; purchInput.className='part-purchase'; purchInput.addEventListener('input',function(){formatInput(this);});
    purchCell.appendChild(purchInput);
    const actCell = row.insertCell();
    const delBtn = document.createElement('button'); delBtn.textContent='❌'; delBtn.className='btn small danger'; delBtn.onclick=()=>{row.remove();updatePartsTotal();};
    actCell.appendChild(delBtn);
    if(partId) fetchPartInfo(partId, priceInput, purchInput);
}
function updatePartsTotal() {
    let total=0;
    document.querySelectorAll('#partsTable tbody tr').forEach(row=>{
        const q=parseInt(row.querySelector('.part-qty')?.value)||0, p=parseNumber(row.querySelector('.part-price')?.value);
        total+=q*p;
    });
    const el=document.getElementById('partsTotal'); if(el) el.textContent=total.toLocaleString('en-US');
}

// ========== دریافت اطلاعات قطعه ==========
function fetchPartInfo(partId, priceInput, purchaseInput) {
    if(!partId) return;
    fetch('ajax_handler.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=get_part_info&part_id='+encodeURIComponent(partId)})
    .then(r=>r.json()).then(data=>{
        if(data.success){
            if(data.default_sale_price && priceInput){ priceInput.value=Number(data.default_sale_price).toLocaleString('en-US'); updatePartsTotal(); }
            if(data.default_purchase_price && purchaseInput) purchaseInput.value=Number(data.default_purchase_price).toLocaleString('en-US');
        }
    });
}

// ========== افزودن سریع ==========
let currentAddType=null;
function addNewItem(type){
    currentAddType=type;
    const titles={customer:'افزودن مشتری جدید',device_type:'افزودن نوع دستگاه جدید',service_type:'افزودن نوع خدمت جدید',technician:'افزودن تکنسین جدید',part:'افزودن قطعه جدید'};
    document.getElementById('add-item-title').textContent=titles[type];
    let fieldsHtml='';
    if(type==='customer'){
        fieldsHtml=`<input id="new-customer-name" placeholder="نام و نام خانوادگی" required><input id="new-customer-phone" placeholder="شماره تماس اصلی *" required><input id="new-customer-phone2" placeholder="شماره تماس ثانویه"><select id="new-customer-type"><option value="personal">شخصی</option><option value="company">شرکتی / مجتمع</option></select><input id="new-customer-company" placeholder="نام شرکت / مجتمع"><input id="new-customer-address" placeholder="آدرس"><input id="new-customer-email" placeholder="ایمیل"><textarea id="new-customer-notes" placeholder="توضیحات"></textarea>`;
    }else{
        fieldsHtml=`<input id="new-item-name" placeholder="نام" required>`;
    }
    document.getElementById('add-item-fields').innerHTML=fieldsHtml;
    openModal('add-item');
}
document.addEventListener('click',function(e){
    if(e.target.id==='save-new-item'){
        if(!currentAddType) return;
        let payload=new URLSearchParams(); payload.append('action','add_'+currentAddType);
        if(currentAddType==='customer'){
            const n=document.getElementById('new-customer-name')?.value.trim(), ph=document.getElementById('new-customer-phone')?.value.trim();
            if(!n||!ph){alert('نام و تلفن اصلی ضروری است.');return;}
            payload.append('name',n); payload.append('phone',ph);
            payload.append('phone2',document.getElementById('new-customer-phone2')?.value||'');
            payload.append('customer_type',document.getElementById('new-customer-type')?.value||'personal');
            payload.append('company_name',document.getElementById('new-customer-company')?.value||'');
            payload.append('address',document.getElementById('new-customer-address')?.value||'');
            payload.append('email',document.getElementById('new-customer-email')?.value||'');
            payload.append('notes',document.getElementById('new-customer-notes')?.value||'');
        }else{
            const n=document.getElementById('new-item-name')?.value.trim();
            if(!n){alert('نام نمی‌تواند خالی باشد.');return;}
            payload.append('name',n);
        }
        fetch('ajax_handler.php',{method:'POST',body:payload}).then(r=>r.json()).then(data=>{
            if(data.success){
                const selMap={customer:'customer_select',device_type:'device_type_select',service_type:'service_type_select',technician:'technician_select',part:'part_select'};
                const selId=selMap[currentAddType];
                if(selId){const sel=document.getElementById(selId); if(sel){sel.add(new Option(data.name,data.id));sel.value=data.id;}}
                if(currentAddType==='part'){
                    const tpl=document.getElementById('partsSelectTemplate'); if(tpl) tpl.innerHTML+=`<option value="${data.id}">${data.name}</option>`;
                    document.querySelectorAll('#partsTable select').forEach(sel=>sel.innerHTML+=`<option value="${data.id}">${data.name}</option>`);
                }
                closeModal('add-item'); document.getElementById('add-item-fields').innerHTML=''; currentAddType=null;
            }else alert('❌ '+data.message);
        }).catch(()=>alert('خطا در ارتباط با سرور.'));
    }
});

// ========== ثبت نهایی سرویس ==========
function submitAll(){
    saveSection('financial');
    if(!serviceData.customer_id){alert('لطفاً مشتری را انتخاب کنید.');return;}
    if(!serviceData.fault_description){alert('شرح مشکل الزامی است.');return;}
    const fd=new FormData();
    for(let[k,v]of Object.entries(serviceData)) fd.append(k,k==='parts'?JSON.stringify(v):(v??''));
    fetch('save_service.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success){alert('✅ سرویس با موفقیت ثبت شد. شماره فاکتور: '+data.invoice_number);window.location.href='index.php';}
        else alert('❌ '+data.message);
    }).catch(()=>alert('خطا در اتصال به سرور.'));
}

// ========== مدیریت تب‌ها ==========
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('.tab-btn').forEach(btn=>{
        btn.addEventListener('click',function(){
            const tabId=this.dataset.tab;
            this.parentElement.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
            const panel=document.getElementById('tab-'+tabId); if(panel) panel.classList.add('active');
        });
    });
    const editForm=document.getElementById('editForm');
    if(editForm){
        editForm.addEventListener('submit',function(e){
            let parts=[];
            document.querySelectorAll('#partsTable tbody tr').forEach(row=>{
                const s=row.querySelector('select'),q=row.querySelector('.part-qty'),p=row.querySelector('.part-price'),pp=row.querySelector('.part-purchase');
                if(s&&s.value&&q&&p) parts.push({part_id:s.value,quantity:parseInt(q.value)||1,unit_price:parseNumber(p.value),purchase_price:pp?parseNumber(pp.value):0});
            });
            document.getElementById('partsInput').value=JSON.stringify(parts);
        });
    }
    // مشتریان
    const customerForm=document.getElementById('customerForm');
    if(customerForm){
        customerForm.addEventListener('submit',function(e){
            e.preventDefault();
            const id=document.getElementById('custId')?.value||'';
            const action=id?'update_customer':'add_customer';
            const payload=new URLSearchParams();
            payload.append('action',action); if(id) payload.append('id',id);
            payload.append('name',document.getElementById('custName')?.value||'');
            payload.append('phone',document.getElementById('custPhone')?.value||'');
            payload.append('phone2',document.getElementById('custPhone2')?.value||'');
            payload.append('customer_type',document.getElementById('custType')?.value||'personal');
            payload.append('company_name',document.getElementById('custCompany')?.value||'');
            payload.append('address',document.getElementById('custAddress')?.value||'');
            payload.append('email',document.getElementById('custEmail')?.value||'');
            payload.append('notes',document.getElementById('custNotes')?.value||'');
            fetch('ajax_handler.php',{method:'POST',body:payload}).then(r=>r.json()).then(data=>{
                if(data.success) location.reload(); else alert(data.message);
            }).catch(()=>alert('خطا'));
        });
    }
    // تأمین‌کنندگان
    const supplierForm=document.getElementById('supplierForm');
    if(supplierForm){
        supplierForm.addEventListener('submit',function(e){
            e.preventDefault();
            const id=document.getElementById('supId')?.value||'';
            const action=id?'update_supplier':'add_supplier';
            const payload=new URLSearchParams();
            payload.append('action',action); if(id) payload.append('id',id);
            payload.append('name',document.getElementById('supName')?.value||'');
            payload.append('phone',document.getElementById('supPhone')?.value||'');
            fetch('ajax_handler.php',{method:'POST',body:payload}).then(r=>r.json()).then(data=>{
                if(data.success) location.reload(); else alert(data.message);
            });
        });
    }
    // خرید
    const purchaseForm=document.getElementById('purchaseForm');
    if(purchaseForm){
        purchaseForm.addEventListener('submit',function(e){
            e.preventDefault();
            const supplierId=document.getElementById('purchaseSupplier')?.value;
            const date=document.getElementById('purchaseDateHidden')?.value;
            if(!supplierId||!date){alert('تأمین‌کننده و تاریخ الزامی است.');return;}
            const items=[];
            document.querySelectorAll('#purchaseItemsTable tbody tr').forEach(row=>{
                const sel=row.querySelector('select'),qty=row.querySelector('input[type=number]'),price=row.querySelector('input[type=text]');
                if(sel&&sel.value&&qty&&qty.value) items.push({part_id:sel.value,quantity:parseInt(qty.value),unit_price:parseNumber(price?.value)});
            });
            if(!items.length){alert('حداقل یک قطعه اضافه کنید.');return;}
            const payload=new URLSearchParams();
            payload.append('action','add_purchase');
            payload.append('supplier_id',supplierId);
            payload.append('date',date);
            payload.append('invoice',document.getElementById('purchaseInvoice')?.value||'');
            payload.append('notes',document.getElementById('purchaseNotes')?.value||'');
            payload.append('items',JSON.stringify(items));
            fetch('ajax_handler.php',{method:'POST',body:payload}).then(r=>r.json()).then(data=>{
                if(data.success){alert('خرید با موفقیت ثبت شد.');location.reload();}
                else alert(data.message);
            });
        });
    }
    // تراکنش
    const transForm=document.getElementById('transactionForm');
    if(transForm){
        transForm.addEventListener('submit',function(e){
            e.preventDefault();
            const payload=new URLSearchParams();
            payload.append('action','add_transaction');
            payload.append('customer_id',document.getElementById('transCustomerId')?.value||'');
            payload.append('amount',parseNumber(document.getElementById('transAmount')?.value));
            payload.append('date',document.getElementById('transDateHidden')?.value||'');
            payload.append('repair_id',document.getElementById('transRepair')?.value||'');
            payload.append('desc',document.getElementById('transDesc')?.value||'');
            fetch('ajax_handler.php',{method:'POST',body:payload}).then(r=>r.json()).then(data=>{
                if(data.success){alert('پرداخت ثبت شد.');location.reload();}
                else alert(data.message);
            });
        });
    }
    initDatePickers();
});

// ========== توابع عمومی مشتریان ==========
function openCustomerModal(){
    document.getElementById('customerModalTitle').textContent='➕ افزودن مشتری جدید';
    document.getElementById('custId').value='';
    document.getElementById('custName').value=''; document.getElementById('custPhone').value=''; document.getElementById('custPhone2').value='';
    document.getElementById('custType').value='personal'; document.getElementById('custCompany').value='';
    document.getElementById('custAddress').value=''; document.getElementById('custEmail').value=''; document.getElementById('custNotes').value='';
    document.getElementById('customerModal').style.display='flex';
}
function editCustomer(customer){
    document.getElementById('customerModalTitle').textContent='✏️ ویرایش مشتری';
    document.getElementById('custId').value=customer.id;
    document.getElementById('custName').value=customer.name||''; document.getElementById('custPhone').value=customer.phone||'';
    document.getElementById('custPhone2').value=customer.phone2||''; document.getElementById('custType').value=customer.customer_type||'personal';
    document.getElementById('custCompany').value=customer.company_name||''; document.getElementById('custAddress').value=customer.address||'';
    document.getElementById('custEmail').value=customer.email||''; document.getElementById('custNotes').value=customer.notes||'';
    document.getElementById('customerModal').style.display='flex';
}
function closeCustomerModal(){ document.getElementById('customerModal').style.display='none'; }

// ========== توابع تأمین‌کنندگان ==========
function openSupplierModal(){
    document.getElementById('supplierModalTitle').textContent='➕ افزودن تأمین‌کننده';
    document.getElementById('supId').value=''; document.getElementById('supName').value=''; document.getElementById('supPhone').value='';
    document.getElementById('supplierModal').style.display='flex';
}
function editSupplier(data){
    document.getElementById('supplierModalTitle').textContent='✏️ ویرایش تأمین‌کننده';
    document.getElementById('supId').value=data.id; document.getElementById('supName').value=data.name; document.getElementById('supPhone').value=data.phone||'';
    document.getElementById('supplierModal').style.display='flex';
}
function closeSupplierModal(){ document.getElementById('supplierModal').style.display='none'; }

// ========== توابع خرید ==========
function addPurchaseItemRow(){
    const tbody=document.querySelector('#purchaseItemsTable tbody');
    const row=tbody.insertRow();
    const selCell=row.insertCell(); const select=document.createElement('select');
    select.innerHTML=document.getElementById('purchasePartsTemplate').innerHTML;
    selCell.appendChild(select);
    const qtyCell=row.insertCell(); const qtyInput=document.createElement('input'); qtyInput.type='number'; qtyInput.value=1; qtyInput.min=1;
    qtyCell.appendChild(qtyInput);
    const priceCell=row.insertCell(); const priceInput=document.createElement('input'); priceInput.type='text'; priceInput.oninput=function(){formatInput(this);};
    priceCell.appendChild(priceInput);
    const actCell=row.insertCell(); const delBtn=document.createElement('button'); delBtn.textContent='❌'; delBtn.className='btn small danger'; delBtn.onclick=()=>row.remove();
    actCell.appendChild(delBtn);
}

// ========== توابع تراکنش ==========
function openTransactionModal(customerId){
    document.getElementById('transCustomerId').value=customerId;
    document.getElementById('transactionModal').style.display='flex';
}
function closeTransactionModal(){ document.getElementById('transactionModal').style.display='none'; }

// ========== مودال حذف ==========
function openDeleteModal(id){ const m=document.getElementById('deleteModal'); const l=document.getElementById('confirmDeleteBtn'); if(m&&l){l.href='index.php?delete_id='+id;m.style.display='flex';} }
function closeDeleteModal(){ const m=document.getElementById('deleteModal'); if(m)m.style.display='none'; }
window.addEventListener('click',function(e){ const m=document.getElementById('deleteModal'); if(e.target===m) closeDeleteModal(); });

// ========== تقویم ==========
function initDatePickers(){
    document.querySelectorAll('.datepicker-input').forEach(input=>{
        const hidden=input.nextElementSibling;
        if(!hidden||hidden.type!=='hidden') return;
        const picker=document.createElement('div'); picker.className='datepicker-dropdown';
        picker.innerHTML=`<div class="dp-header"><button type="button" class="dp-prev">◀</button><span class="dp-year-month"></span><button type="button" class="dp-next">▶</button></div><div class="dp-weekdays"><span>ش</span><span>ی</span><span>د</span><span>س</span><span>چ</span><span>پ</span><span>ج</span></div><div class="dp-days"></div>`;
        input.parentNode.appendChild(picker);
        let cy,cm,cd;
        function render(){
            if(!cy){
                if(hidden.value){ const p=hidden.value.split('/'); cy=+p[0];cm=+p[1];cd=+p[2]; }
                else{ const now=new Date(); const j=jGregorianToJalali(now.getFullYear(),now.getMonth()+1,now.getDate()); cy=j[0];cm=j[1];cd=j[2]; hidden.value=`${cy}/${String(cm).padStart(2,'0')}/${String(cd).padStart(2,'0')}`; input.value=hidden.value; }
            }
            picker.querySelector('.dp-year-month').textContent=persianMonths[cm-1]+' '+cy;
            const daysDiv=picker.querySelector('.dp-days'); daysDiv.innerHTML='';
            const g=jJalaliToGregorian(cy,cm,1); const firstDay=new Date(g[0],g[1]-1,g[2]); const dow=(firstDay.getDay()+1)%7;
            for(let i=0;i<dow;i++) daysDiv.appendChild(document.createElement('span'));
            const dim=jalaliDaysInMonth(cy,cm);
            for(let d=1;d<=dim;d++){ const sp=document.createElement('span'); sp.textContent=d; if(d===cd) sp.classList.add('active'); sp.addEventListener('click',()=>{ cd=d; const val=`${cy}/${String(cm).padStart(2,'0')}/${String(d).padStart(2,'0')}`; input.value=val; hidden.value=val; picker.style.display='none'; }); daysDiv.appendChild(sp); }
        }
        picker.querySelector('.dp-prev').addEventListener('click',()=>{ if(cm===1){cy--;cm=12;}else cm--; render(); });
        picker.querySelector('.dp-next').addEventListener('click',()=>{ if(cm===12){cy++;cm=1;}else cm++; render(); });
        input.addEventListener('click',function(e){ document.querySelectorAll('.datepicker-dropdown').forEach(dp=>{if(dp!==picker)dp.style.display='none';}); picker.style.display='block'; render(); });
        document.addEventListener('click',function(e){ if(!picker.contains(e.target)&&e.target!==input) picker.style.display='none'; });
    });
}