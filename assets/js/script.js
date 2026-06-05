/**
 * فایل اصلی جاوااسکریپت سیستم مدیریت تعمیرگاه
 * شامل: تاریخ شمسی، مودال‌ها، فرم قطعات پویا، محاسبات، جستجو، حذف، بازگردانی، نمودارها و ...
 * @version 3.0.0
 */
'use strict';

// ======================== ماژول تاریخ شمسی ========================
const PersianDate = (function() {
    const gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    const persianMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    function isJalaliLeap(year) {
        const a = (year - 474) % 2820 + 474;
        return ((a + 38) * 31) % 128 < 31;
    }

    function toJalali(gy, gm, gd) {
        if (gy < 1 || gm < 1 || gm > 12 || gd < 1) return null;
        const gy2 = (gm > 2) ? gy + 1 : gy;
        const days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + gDaysInMonth[gm - 1];
        let jy = -1595 + (33 * Math.floor(days / 12053));
        let daysRemaining = days % 12053;
        jy += 4 * Math.floor(daysRemaining / 1461);
        daysRemaining %= 1461;
        if (daysRemaining > 365) {
            jy += Math.floor((daysRemaining - 1) / 365);
            daysRemaining = (daysRemaining - 1) % 365;
        }
        let jm = (daysRemaining < 186) ? 1 + Math.floor(daysRemaining / 31) : 7 + Math.floor((daysRemaining - 186) / 30);
        let jd = 1 + ((daysRemaining < 186) ? (daysRemaining % 31) : ((daysRemaining - 186) % 30));
        return { year: jy, month: jm, day: jd, monthName: persianMonths[jm-1] };
    }

    function toGregorian(jy, jm, jd) {
        // کد تبدیل معکوس (اختصاری)
        return { year: 1400, month: 1, day: 1 }; // placeholder
    }

    function getToday() {
        const d = new Date();
        return toJalali(d.getFullYear(), d.getMonth()+1, d.getDate());
    }

    function formatJalali(date, format = 'YYYY/MM/DD') {
        if (!date) return '';
        return format.replace('YYYY', date.year).replace('MM', date.month.toString().padStart(2,'0')).replace('DD', date.day.toString().padStart(2,'0'));
    }

    return { toJalali, toGregorian, getToday, formatJalali, persianMonths };
})();

// ======================== ماژول UI (مودال، جستجو، تب) ========================
const UI = (function() {
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) { modal.style.display = 'block'; document.body.style.overflow = 'hidden'; }
    }
    function closeModal(modalId) {
        const modal = modalId ? document.getElementById(modalId) : document.querySelector('.modal[style*="block"]');
        if (modal) { modal.style.display = 'none'; document.body.style.overflow = ''; }
    }
    function setupLiveSearch(inputSel, tableSel, rowSel = 'tbody tr') {
        const input = document.querySelector(inputSel);
        if (!input) return;
        input.addEventListener('keyup', () => {
            const filter = input.value.trim().toLowerCase();
            document.querySelectorAll(`${tableSel} ${rowSel}`).forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
    function initTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-tab');
                if (!targetId) return;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                const target = document.getElementById(targetId);
                if (target) target.classList.add('active');
            });
        });
    }
    function init() {
        document.querySelectorAll('.modal-close, .close-modal').forEach(btn => {
            btn.addEventListener('click', () => closeModal());
        });
        window.addEventListener('click', (e) => {
            if (e.target.classList && e.target.classList.contains('modal')) closeModal();
        });
        if (document.querySelector('#searchInput')) setupLiveSearch('#searchInput', '.table');
        initTabs();
    }
    return { openModal, closeModal, setupLiveSearch, init };
})();

// ======================== ماژول فرم قطعات پویا و محاسبات ========================
const DynamicForms = (function() {
    let partIndex = 0;
    function addPartRow(containerId, partSelectOptions = '') {
        const container = document.getElementById(containerId);
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'part-row';
        row.setAttribute('data-part-index', partIndex);
        row.innerHTML = `
            <select name="part_id[]" class="form-control part-select" required>
                <option value="">انتخاب قطعه</option>
                ${partSelectOptions}
            </select>
            <input type="number" name="part_quantity[]" class="form-control part-quantity" placeholder="تعداد" step="1" min="1" value="1">
            <input type="number" name="part_price[]" class="form-control part-price" placeholder="قیمت واحد (تومان)" step="1000">
            <button type="button" class="remove-part" onclick="DynamicForms.removePartRow(this)">×</button>
        `;
        container.appendChild(row);
        partIndex++;
        attachPartEvents(row);
    }
    function removePartRow(btn) {
        const row = btn.closest('.part-row');
        if (row) row.remove();
        calculateTotal();
    }
    function attachPartEvents(row) {
        const qty = row.querySelector('.part-quantity');
        const price = row.querySelector('.part-price');
        if (qty && price) {
            qty.addEventListener('input', calculateTotal);
            price.addEventListener('input', calculateTotal);
        }
    }
    function calculateTotal() {
        let totalParts = 0;
        document.querySelectorAll('.part-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.part-quantity')?.value) || 0;
            const price = parseFloat(row.querySelector('.part-price')?.value) || 0;
            totalParts += qty * price;
        });
        const totalPartsField = document.querySelector('#total_parts_cost');
        if (totalPartsField) totalPartsField.value = totalParts.toLocaleString('fa-IR');
        // جمع کل با دستمزد
        const laborCost = parseFloat(document.querySelector('#labor_cost')?.value) || 0;
        const grandTotal = totalParts + laborCost;
        const grandTotalField = document.querySelector('#grand_total');
        if (grandTotalField) grandTotalField.value = grandTotal.toLocaleString('fa-IR');
    }
    function init(selectOptionsHtml = '') {
        partIndex = 0;
        document.querySelectorAll('.add-part-btn').forEach(btn => {
            btn.addEventListener('click', () => addPartRow('parts-container', selectOptionsHtml));
        });
        document.querySelectorAll('.part-quantity, .part-price').forEach(el => {
            el.addEventListener('input', calculateTotal);
        });
        document.querySelector('#labor_cost')?.addEventListener('input', calculateTotal);
        calculateTotal();
    }
    return { addPartRow, removePartRow, calculateTotal, init };
})();

// ======================== ماژول حذف با تأیید و بازگردانی ========================
const Actions = (function() {
    function confirmDelete(url, itemName, useSweet = false) {
        if (useSweet && typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'تأیید حذف',
                text: `آیا از حذف "${itemName}" مطمئن هستید؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'بله، حذف کن',
                cancelButtonText: 'انصراف'
            }).then((result) => { if (result.isConfirmed) window.location.href = url; });
        } else {
            if (confirm(`آیا از حذف "${itemName}" مطمئن هستید؟`)) window.location.href = url;
        }
    }
    function undoAction(undoUrl, restoreUrl) {
        if (!confirm('آیا می‌خواهید آخرین تغییر را بازگردانید؟')) return;
        fetch(undoUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                if (data.success) window.location.href = restoreUrl || window.location.href;
                else alert('خطا: ' + (data.message || 'ناموفق'));
            })
            .catch(err => alert('خطا در ارتباط با سرور'));
    }
    function init() {
        document.querySelectorAll('.delete-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const url = link.getAttribute('href');
                const name = link.getAttribute('data-name') || 'آیتم';
                const useSweet = link.hasAttribute('data-swal') || (typeof Swal !== 'undefined');
                confirmDelete(url, name, useSweet);
            });
        });
        document.querySelectorAll('.undo-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const undoUrl = link.getAttribute('data-undo-url') || link.getAttribute('href');
                const restoreUrl = link.getAttribute('data-restore-url');
                undoAction(undoUrl, restoreUrl);
            });
        });
    }
    return { confirmDelete, undoAction, init };
})();

// ======================== ماژول گزارشات و نمودارها ========================
const Reports = (function() {
    function renderBarChart(containerId, data) {
        const container = document.getElementById(containerId);
        if (!container || !data || !data.labels) return;
        let html = '<div class="bar-chart">';
        data.labels.forEach((label, i) => {
            const value = data.values[i] || 0;
            const max = Math.max(...data.values, 1);
            const percent = (value / max) * 100;
            html += `<div class="bar-item">
                        <span class="bar-label">${label}</span>
                        <div class="bar-wrapper"><div class="bar-fill" style="width: ${percent}%">${value.toLocaleString('fa-IR')}</div></div>
                     </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }
    function init() {
        const chartElement = document.querySelector('[data-chart]');
        if (chartElement) {
            try {
                const chartData = JSON.parse(chartElement.getAttribute('data-chart'));
                if (chartData) renderBarChart(chartElement.id, chartData);
            } catch(e) {}
        }
    }
    return { renderBarChart, init };
})();

// ======================== ماژول چاپ فاکتور ========================
const Invoice = (function() {
    function printInvoice() { window.print(); }
    return { printInvoice };
})();

// ======================== مقداردهی اولیه کلی ========================
document.addEventListener('DOMContentLoaded', function() {
    UI.init();
    Actions.init();
    Reports.init();

    // اگر فرم ایجاد سرویس باشد، ماژول فرم پویا را مقداردهی کن
    if (document.getElementById('parts-container')) {
        // دریافت لیست قطعات از یک المان مخفی یا AJAX
        let partOptions = '';
        const partSelectSource = document.getElementById('part-options-json');
        if (partSelectSource) {
            try {
                const parts = JSON.parse(partSelectSource.value);
                partOptions = parts.map(p => `<option value="${p.id}">${p.name} - ${p.stock} عدد</option>`).join('');
            } catch(e) {}
        }
        DynamicForms.init(partOptions);
    }

    // فعال‌سازی تاریخ شمسی روی inputهای دارای کلاس datepicker (اگر کتابخانه لود شده باشد)
    if (typeof $.fn !== 'undefined' && $.fn.persianDatepicker) {
        $('.datepicker').persianDatepicker({ format: 'YYYY/MM/DD', autoClose: true });
    } else {
        // Fallback: مقداردهی پیش‌فرض امروز
        document.querySelectorAll('.datepicker').forEach(input => {
            if (!input.value) {
                const today = PersianDate.getToday();
                input.value = PersianDate.formatJalali(today);
            }
        });
    }

    // تابع سراسری برای استفاده در onclick های باقی‌مانده (موقت)
    window.openModal = UI.openModal;
    window.closeModal = UI.closeModal;
    window.addPartRow = DynamicForms.addPartRow;
    window.removePartRow = DynamicForms.removePartRow;
    window.calculateTotal = DynamicForms.calculateTotal;
    window.confirmDelete = Actions.confirmDelete;
    window.undoAction = Actions.undoAction;
    window.printInvoice = Invoice.printInvoice;
});