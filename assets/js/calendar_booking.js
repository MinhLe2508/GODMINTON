/* =================================================================
    CALENDAR BOOKING GRID ENGINE - Static UI Version
    ================================================================= */

/* -------- Config -------- */
const START_HOUR = 5, START_MIN = 30;  // 5:30
const END_HOUR   = 24, END_MIN  = 0;   // 24:00
const SLOT_MINS  = 30;                 // 30-min cells

const COURTS = [
    { id: 0, name: 'Thảm A1', sub: 'Sân thảm nhựa PVC' },
    { id: 1, name: 'Thảm A2', sub: 'Sân thảm nhựa PVC' },
    { id: 2, name: 'Thảm A3', sub: 'Sân cao su chuyên dụng' },
    { id: 3, name: 'Thảm A4', sub: 'Sân cao su chuyên dụng' },
];

/* -------- Build time slots array -------- */
function buildTimeSlots() {
    const slots = [];
    let h = START_HOUR, m = START_MIN;
    while (h < END_HOUR || (h === END_HOUR && m < END_MIN)) {
        slots.push({ h, m, label: `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}` });
        m += SLOT_MINS;
        if (m >= 60) { m -= 60; h++; }
    }
    return slots;
}
const TIME_SLOTS = buildTimeSlots(); // 37 slots

/* -------- Mock events for demo -------- */
const DEMO_EVENTS = {
    0: [ // Thảm A1
        { type:'booked',  startIdx:11, span:2, label:'', sub:'' },        // 11:00–12:00
        { type:'event',   startIdx:17, span:8, label:'[Social] Trình trung bình', sub:'4/8 người • 17:00–21:00' },
        { type:'locked',  startIdx:0,  span:3, label:'Bảo trì', sub:'05:30–07:00' },
    ],
    1: [ // Thảm A2
        { type:'booked',  startIdx:5,  span:2, label:'', sub:'' },
        { type:'booked',  startIdx:14, span:2, label:'', sub:'' },
        { type:'event',   startIdx:21, span:6, label:'[Tournament] Giải nội bộ', sub:'0/16 suất • 18:30–21:30' },
    ],
    2: [ // Thảm A3
        { type:'holding', startIdx:13, span:2, label:'Bạn đang giữ', sub:'16:00–17:00' },
        { type:'booked',  startIdx:20, span:4, label:'', sub:'' },
    ],
    3: [ // Thảm A4
        { type:'locked',  startIdx:0,  span:2, label:'Khoá', sub:'05:30–06:30' },
        { type:'event',   startIdx:9,  span:4, label:'[CLB] Buổi sinh hoạt CLB', sub:'2/12 • 10:00–12:00' },
        { type:'booked',  startIdx:17, span:2, label:'', sub:'' },
        { type:'booked',  startIdx:25, span:3, label:'', sub:'' },
    ],
};

/* -------- Render Calendar Table -------- */
function renderCalendar(visibleCourts) {
    const table = document.getElementById('calGrid');
    if (!table) return;

    // Get current hour/min for "now" indicator
    const now = new Date();
    const nowTotal = now.getHours()*60 + now.getMinutes();
    const startTotal = START_HOUR*60 + START_MIN;

    // ---- Build header row ----
    let thead = `<thead><tr>`;
    thead += `<th class="cal-th-court">
                <div style="font-size:0.7rem;font-weight:800;color:var(--text-secondary);text-align:center;">SÂN \\ GIỜ</div>
                </th>`;

    TIME_SLOTS.forEach((slot, idx) => {
        const isHour = slot.m === 0;
        const isCurrentHour = (now.getHours() === slot.h && now.getMinutes() < 30)
                            || (now.getHours() === slot.h && slot.m === 30 && now.getMinutes() >= 30);
        thead += `<th class="cal-th-time${isHour ? ' hour-mark' : ''}${isCurrentHour ? ' current-hour' : ''}" data-idx="${idx}">
                    ${slot.label}
                    </th>`;
    });
    thead += `</tr></thead>`;

    // ---- Build body rows ----
    let tbody = `<tbody>`;
    visibleCourts.forEach(court => {
        const events = DEMO_EVENTS[court.id] || [];

        // Build a map: slotIdx → event (if event occupies it)
        const eventMap = {};
        events.forEach(ev => {
            for (let i = ev.startIdx; i < ev.startIdx + ev.span; i++) {
                eventMap[i] = ev;
            }
        });

        tbody += `<tr>`;
        // Court label cell
        tbody += `<td class="cal-td-court-label">
                    <div class="court-label-inner">
                        <span class="court-label-name">${court.name}</span>
                        <span class="court-label-sub">${court.sub}</span>
                    </div>
                    </td>`;

        // Time slot cells
        let i = 0;
        while (i < TIME_SLOTS.length) {
            const slot = TIME_SLOTS[i];
            const isHourStart = slot.m === 0;

            if (eventMap[i]) {
                const ev = eventMap[i];
                // Only render the opening cell (first cell of event)
                if (ev.startIdx === i) {
                    let tdClass = 'cal-td-slot ';
                    let blockClass = 'cal-slot-block ';
                    if (ev.type === 'booked')   { tdClass += 'cal-td-booked';  blockClass += 'block-booked'; }
                    if (ev.type === 'locked')   { tdClass += 'cal-td-locked';  blockClass += 'block-locked'; }
                    if (ev.type === 'event')    { tdClass += 'cal-td-event';   blockClass += 'block-event'; }
                    if (ev.type === 'holding')  { tdClass += 'cal-td-holding'; blockClass += 'block-holding'; }
                    if (isHourStart) tdClass += ' hour-start';

                    const timeLabel = `${TIME_SLOTS[ev.startIdx].label} – ${TIME_SLOTS[Math.min(ev.startIdx+ev.span, TIME_SLOTS.length-1)].label}`;
                    const icon = ev.type==='booked'?'fa-ban':ev.type==='locked'?'fa-lock':ev.type==='event'?'fa-calendar-star':'fa-stopwatch';

                    tbody += `<td class="${tdClass}" colspan="${ev.span}"
                                    onclick="handleSlotClick(${court.id}, ${i}, '${ev.type}')">
                                <div class="${blockClass}" title="${ev.label || ev.type}">
                                    <div class="block-time-label">
                                        <i class="fa-solid ${icon} me-1" style="font-size:0.6rem;"></i>${timeLabel}
                                    </div>
                                    ${ev.label ? `<div class="block-title">${ev.label}</div>` : ''}
                                    ${ev.sub   ? `<div class="block-sub">${ev.sub}</div>` : ''}
                                </div>
                                </td>`;
                    i += ev.span;
                    continue;
                }
                // Skip cells already covered by colspan
                i++;
                continue;
            }

            // Empty / available cell
            const cellClass = `cal-td-slot s-available${isHourStart ? ' hour-start' : ''}`;
            tbody += `<td class="${cellClass}"
                            data-court="${court.id}" data-slot="${i}"
                            onclick="handleSlotClick(${court.id}, ${i}, 'available')"
                            onmouseenter="highlightCell(this)"
                            onmouseleave="unhighlightCell(this)">
                        </td>`;
            i++;
        }
        tbody += `</tr>`;
    });
    tbody += `</tbody>`;

    table.innerHTML = thead + tbody;
}

/* -------- Click handlers -------- */
let selectedSlots = []; // { courtId, slotIdx }

function handleSlotClick(courtId, slotIdx, type) {
    if (type === 'booked' || type === 'locked') {
        Swal.fire({
            icon: type === 'locked' ? 'info' : 'error',
            title: type === 'locked' ? 'Khung giờ đóng cửa' : 'Khung giờ đã được đặt',
            text: type === 'locked'
                ? 'Sân đang trong thời gian bảo trì hoặc đóng cửa theo lịch.'
                : 'Khung giờ này đã có khách đặt. Vui lòng chọn ô màu trắng còn trống.',
            confirmButtonColor: '#0d9488',
            toast: false
        });
        return;
    }
    if (type === 'event') {
        Swal.fire({
            icon: 'info',
            title: 'Sự kiện / Lịch câu lạc bộ',
            html: `<p class="mb-1">Khung giờ này đã có sự kiện hoặc sinh hoạt CLB đang đăng ký.</p>
                    <p class="small text-muted">Liên hệ hotline <strong>1900 8888</strong> để đăng ký tham gia sự kiện.</p>`,
            confirmButtonColor: '#0d9488',
        });
        return;
    }
    if (type === 'holding') {
        Swal.fire({
            icon: 'success',
            title: 'Bạn đang giữ chỗ này',
            html: `Bạn có <strong>5 phút</strong> để xác nhận đặt cọc. 
                    <a href="payment.php" class="d-block mt-2 text-teal fw-bold text-decoration-none">Đặt cọc & Thanh toán ngay →</a>`,
            confirmButtonColor: '#0d9488',
            showCancelButton: true,
            cancelButtonText: 'Hủy giữ chỗ',
            cancelButtonColor: '#ef4444'
        });
        return;
    }

    // Available - toggle select
    const cell = document.querySelector(`td[data-court="${courtId}"][data-slot="${slotIdx}"]`);
    if (!cell) return;

    const existingIdx = selectedSlots.findIndex(s => s.courtId===courtId && s.slotIdx===slotIdx);
    if (existingIdx >= 0) {
        selectedSlots.splice(existingIdx, 1);
        cell.style.background = '';
        cell.style.outline = '';
    } else {
        selectedSlots.push({ courtId, slotIdx });
        cell.style.background = 'rgba(99,102,241,0.18)';
        cell.style.outline = '2px solid #6366f1';
        cell.style.outlineOffset = '-2px';
    }

    updateSummaryBar();
}

function updateSummaryBar() {
    const bar = document.getElementById('bookingSummaryBar');
    if (!bar) return;
    if (selectedSlots.length === 0) {
        bar.style.display = 'none';
        return;
    }
    bar.style.display = 'flex';
    const first = selectedSlots[0];
    const court = COURTS.find(c => c.id === first.courtId);
    const slot  = TIME_SLOTS[first.slotIdx];
    const lastSlot = TIME_SLOTS[Math.min(first.slotIdx + selectedSlots.length, TIME_SLOTS.length-1)];
    const priceEst = selectedSlots.length * (court?.price_per_slot || 45000);
    const depositEst = Math.round(priceEst * 0.5);
    document.getElementById('summaryTitle').textContent =
        `${court.name} • ${slot.label} – ${lastSlot.label}${selectedSlots.length > 1 ? ` (${selectedSlots.length} ô)` : ''}`;
    document.getElementById('summaryDetail').textContent =
        `${(priceEst/1000).toFixed(0)}k đ ước tính • Đặt cọc ${(depositEst/1000).toFixed(0)}k đ`;

    // Update payment button URL with booking params
    const payBtn = document.getElementById('btnGoPayment');
    if (payBtn && activeDateStr) {
        const params = new URLSearchParams({
            court_id:  first.courtId,
            date:      activeDateStr,
            start:     slot.label,
            end:       lastSlot.label,
            court_num: `Sân ${first.courtId}`
        });
        payBtn.href = `payment.php?${params.toString()}`;
    }
}

function highlightCell(el) { el.classList.add('hover-highlight'); }
function unhighlightCell(el) { el.classList.remove('hover-highlight'); }

/* -------- Date pills -------- */
let currentWeekStart = null;
let activeDateStr = null;

function getWeekDates(mondayDate) {
    const dates = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(mondayDate);
        d.setDate(d.getDate() + i);
        dates.push(d);
    }
    return dates;
}

function getMondayOf(date) {
    const d = new Date(date);
    const day = d.getDay(); // 0=Sun
    const diff = (day === 0) ? -6 : 1 - day;
    d.setDate(d.getDate() + diff);
    d.setHours(0,0,0,0);
    return d;
}

const DAY_NAMES = ['CN','T2','T3','T4','T5','T6','T7'];

function renderDatePills(weekStart) {
    const dates = getWeekDates(weekStart);
    const row = document.getElementById('datePillRow');
    const today = new Date(); today.setHours(0,0,0,0);

    // Week range label
    const label = document.getElementById('weekRangeLabel');
    const fmt = d => `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}`;
    label.textContent = `${fmt(dates[0])} – ${fmt(dates[6])}/${dates[6].getFullYear()}`;

    row.innerHTML = dates.map(d => {
        const dStr = d.toISOString().split('T')[0];
        const isActive = (dStr === activeDateStr);
        const isToday = (d.getTime() === today.getTime());
        const isPast = d < today;
        return `<div class="date-pill${isActive?' active':''}${isPast?' opacity-40':''}" data-date="${dStr}" onclick="selectDate('${dStr}')">
                    <span class="day-name">${isToday?'HN':DAY_NAMES[d.getDay()]}</span>
                    <span class="day-num">${d.getDate()}</span>
                </div>`;
    }).join('');
}

function selectDate(dateStr) {
    activeDateStr = dateStr;
    document.getElementById('bookingDateInput').value = dateStr;
    renderDatePills(currentWeekStart);
    // In real version: reload slots from MockDB
    renderCalendar(getVisibleCourts());
}

/* -------- Court filter tabs -------- */
function getVisibleCourts() {
    const active = document.querySelector('.court-tab-btn.active');
    if (!active || active.dataset.court === 'all') return COURTS;
    const id = parseInt(active.dataset.court);
    return COURTS.filter(c => c.id === id);
}

/* -------- Scroll arrows -------- */
function initScrollArrows() {
    const container = document.getElementById('calScrollContainer');
    document.getElementById('scrollLeft')?.addEventListener('click', () => {
        container.scrollBy({ left: -container.clientWidth * 0.6, behavior: 'smooth' });
    });
    document.getElementById('scrollRight')?.addEventListener('click', () => {
        container.scrollBy({ left: container.clientWidth * 0.6, behavior: 'smooth' });
    });
}

/* -------- Init -------- */
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date(); today.setHours(0,0,0,0);
    currentWeekStart = getMondayOf(today);
    activeDateStr = today.toISOString().split('T')[0];
    document.getElementById('bookingDateInput').value = activeDateStr;

    renderDatePills(currentWeekStart);
    renderCalendar(COURTS);
    initScrollArrows();

    // Week navigation
    document.getElementById('btnPrevWeek').addEventListener('click', () => {
        currentWeekStart = new Date(currentWeekStart);
        currentWeekStart.setDate(currentWeekStart.getDate() - 7);
        renderDatePills(currentWeekStart);
    });
    document.getElementById('btnNextWeek').addEventListener('click', () => {
        currentWeekStart = new Date(currentWeekStart);
        currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        renderDatePills(currentWeekStart);
    });

    // Date input change
    document.getElementById('bookingDateInput').addEventListener('change', e => {
        selectDate(e.target.value);
        currentWeekStart = getMondayOf(new Date(e.target.value + 'T00:00:00'));
        renderDatePills(currentWeekStart);
    });

    // Court tabs
    document.querySelectorAll('.court-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.court-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            renderCalendar(getVisibleCourts());
        });
    });

    // Cancel selection
    document.getElementById('btnCancelSel')?.addEventListener('click', () => {
        selectedSlots = [];
        document.querySelectorAll('td[data-slot]').forEach(td => {
            td.style.background = '';
            td.style.outline = '';
        });
        updateSummaryBar();
    });

    // Scroll to current time
    const container = document.getElementById('calScrollContainer');
    const now = new Date();
    const nowMins = now.getHours()*60 + now.getMinutes() - (START_HOUR*60+START_MIN);
    const nowSlotIdx = Math.max(0, Math.floor(nowMins / SLOT_MINS) - 2);
    const slotW = 72;
    const courtLabelW = 100;
    if (nowSlotIdx > 0) {
        setTimeout(() => { container.scrollLeft = courtLabelW + nowSlotIdx * slotW; }, 300);
    }
});