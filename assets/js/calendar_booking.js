/* =================================================================
    CALENDAR BOOKING GRID ENGINE - Real-time AJAX Polling Version
    GODMINTON — Tuần 2: Hỗ trợ đặt 1 tiếng / 1 tiếng 30 phút trở lên
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

function formatTimeHHmm(t) {
    if (!t) return '';
    const str = String(t).trim();
    let h = 0, m = 0;
    if (str.includes(':')) {
        const parts = str.split(':');
        h = parseInt(parts[0], 10) || 0;
        m = parseInt(parts[1], 10) || 0;
    } else if (str.toLowerCase().includes('h')) {
        const parts = str.toLowerCase().split('h');
        h = parseInt(parts[0], 10) || 0;
        m = parseInt(parts[1], 10) || 0;
    } else {
        h = parseInt(str, 10) || 0;
    }
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

function formatRangeShort(start, end) {
    return `${formatTimeHHmm(start)}-${formatTimeHHmm(end)}`;
}

/* -------- State -------- */
let activeCourtId = null;       // Selected venue ID, validated by booking.php.
let activeDateStr = null;       // YYYY-MM-DD
let currentWeekStart = null;
let serverSlots = [];           // Danh sách slots thật từ MySQL

// Quản lý việc chọn ô trống trên giao diện (chọn 1h, 1.5h, 2h...)
let selectedCourtId = null;     // ID thảm (0, 1, 2, 3)
let selectedSlotIndices = [];   // Danh sách slotIdx (ví dụ [3, 4] = 1h, [3, 4, 5] = 1.5h)

// Quản lý các slot mà user đang hold thật trong DB
let myHeldSlots = [];           // Mảng các slot mà user đang holding
let pollingTimer = null;        // Timer polling 5s
let countdownTimer = null;      // Timer đếm ngược 1s

// Tiện ích format VND thuần JS
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* -------- Convert Time to Slot Index -------- */
function timeToSlotIdx(timeStr) {
    if (!timeStr) return -1;
    const [h, m] = timeStr.split(':').map(Number);
    const totalMins = h * 60 + m;
    const startMins = START_HOUR * 60 + START_MIN;
    if (totalMins < startMins) return 0;
    return Math.floor((totalMins - startMins) / SLOT_MINS);
}

/* -------- Kiểm tra khung giờ đã qua trong quá khứ -------- */
function isSlotPast(dateStr, slotIdx) {
    if (!dateStr || slotIdx < 0 || slotIdx >= TIME_SLOTS.length) return false;
    const now = new Date();
    const todayStr = formatDateLocal(now);

    // 1. Ngày trước hôm nay -> Đã qua hoàn toàn
    if (dateStr < todayStr) return true;
    // 2. Ngày sau hôm nay -> Tương lai, khả dụng
    if (dateStr > todayStr) return false;

    // 3. Ngày hôm nay: kiểm tra theo giờ phút hiện tại
    const slot = TIME_SLOTS[slotIdx];
    const curHour = now.getHours();
    const curMin = now.getMinutes();

    // Khung giờ đã bắt đầu hoặc đã qua (start_time <= current_time)
    return (slot.h < curHour || (slot.h === curHour && slot.m <= curMin));
}

function handlePastSlotClick(timeLabel) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: `Khung giờ ${timeLabel} đã qua`,
        text: 'Bạn không thể đặt khung giờ trong quá khứ. Vui lòng chọn các khung giờ sắp tới!',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

/* -------- Fetch Slots from MySQL API -------- */
async function fetchSlots(courtId, dateStr, isPolling = false) {
    try {
        const res = await fetch(`api/slots.php?court_id=${courtId}&date=${dateStr}`);
        const result = await res.json();

        if (result.status === 'success') {
            serverSlots = result.data || [];

            if (result.court && document.getElementById('courtNameTitle')) {
                document.getElementById('courtNameTitle').textContent = result.court.name;
            }

            // Lọc ra tất cả các slot mà chính user này đang giữ trong DB
            myHeldSlots = serverSlots.filter(s => s.is_my_hold) || [];

            renderCalendar(getVisibleCourts());
            updateSummaryBar();
        } else {
            console.warn('API slots trả lỗi:', result.message);
        }
    } catch (err) {
        if (!isPolling) {
            console.error('Lỗi khi gọi API slots:', err);
        }
    }
}

/* -------- Map MySQL slots to Calendar events -------- */
function buildEventMapForCourt(courtIndex) {
    const courtName = COURTS[courtIndex]?.name;
    const slotsForCourt = (serverSlots || []).filter(s => s.sub_court_name === courtName);
    const eventMap = {};

    // Sắp xếp các slot theo start_time tăng dần
    slotsForCourt.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));

    let i = 0;
    while (i < slotsForCourt.length) {
        const s = slotsForCourt[i];
        if (s.status === 'available') {
            i++;
            continue;
        }

        let type = 'available';
        if (s.status === 'booked') {
            type = 'booked';
        } else if (s.status === 'maintenance') {
            type = 'maintenance';
        } else if (s.status === 'blocked') {
            type = 'locked';
        } else if (s.status === 'holding') {
            type = s.is_my_hold ? 'holding' : 'locked';
        }

        let startIdx = timeToSlotIdx(s.start_time);
        let endIdx   = timeToSlotIdx(s.end_time);
        let mergedSlots = [s];

        // Gộp các slot liền kề cùng loại (đã đặt, bảo trì, holding, khóa tạm)
        let j = i + 1;
        while (j < slotsForCourt.length) {
            const next = slotsForCourt[j];
            const nextStart = timeToSlotIdx(next.start_time);
            const nextEnd   = timeToSlotIdx(next.end_time);

            let canMerge = false;
            if (nextStart === endIdx) {
                if (type === 'booked' && next.status === 'booked') {
                    canMerge = true;
                } else if (type === 'maintenance' && next.status === 'maintenance') {
                    canMerge = true;
                } else if (type === 'holding' && next.status === 'holding' && next.is_my_hold) {
                    canMerge = true;
                } else if (type === 'locked') {
                    if (s.status === 'holding' && next.status === 'holding' && !next.is_my_hold && next.held_by === s.held_by) {
                        canMerge = true;
                    } else if (s.status === 'blocked' && next.status === 'blocked') {
                        canMerge = true;
                    }
                }
            }

            if (canMerge) {
                endIdx = nextEnd;
                mergedSlots.push(next);
                j++;
            } else {
                break;
            }
        }

        const span = Math.max(1, endIdx - startIdx);
        let label = '';
        let sub = '';

        const evStartTime = mergedSlots[0].start_time;
        const evEndTime   = mergedSlots[mergedSlots.length - 1].end_time;

        if (type === 'booked') {
            const durText = span === 1 ? '30p' : (span === 2 ? '1 tiếng' : (span === 3 ? '1h30p' : `${span * 0.5} tiếng`));
            label = span > 1 ? `Đã đặt (${durText})` : 'Đã đặt';
            sub = '';
        } else if (type === 'maintenance') {
            const durText = span === 1 ? '30p' : (span === 2 ? '1 tiếng' : (span === 3 ? '1h30p' : `${span * 0.5} tiếng`));
            label = span > 1 ? `Bảo trì (${durText})` : 'Đang bảo trì';
            sub = 'Tạm ngưng phục vụ';
        } else if (type === 'holding') {
            const totalSecs = Math.max(0, Math.min(300, s.hold_seconds_left || 0));
            const mins = Math.floor(totalSecs / 60);
            const secs = totalSecs % 60;
            label = '';
            sub = `Còn ${mins}p${secs < 10 ? '0' : ''}${secs}s`;
        } else if (type === 'locked') {
            const durText = span === 1 ? '30p' : (span === 2 ? '1 tiếng' : (span === 3 ? '1h30p' : `${span * 0.5} tiếng`));
            label = s.status === 'blocked' ? (span > 1 ? `Ngừng nhận (${durText})` : 'Ngừng nhận') : 'Đang giữ';
            sub = s.status === 'blocked' ? '' : 'Khách đang cọc';
        }

        const ev = {
            slotId: s.id,
            slotIds: mergedSlots.map(x => x.id),
            type: type,
            startIdx: startIdx,
            span: span,
            startTime: evStartTime,
            endTime: evEndTime,
            label: label,
            sub: sub,
            raw: s
        };

        for (let k = startIdx; k < startIdx + span; k++) {
            eventMap[k] = ev;
        }

        i = j;
    }

    return { eventMap, slotsForCourt };
}

/* -------- Render Calendar Table -------- */
function renderCalendar(visibleCourts) {
    const table = document.getElementById('calGrid');
    if (!table) return;

    const now = new Date();

    // Header row
    let thead = `<thead><tr>`;
    thead += `<th class="cal-th-court">
                <div style="font-size:0.7rem;font-weight:800;color:var(--text-secondary);text-align:center;">SÂN \\ GIỜ</div>
              </th>`;

    TIME_SLOTS.forEach((slot, idx) => {
        const isHour = slot.m === 0;
        const isCurrentHour = (now.getHours() === slot.h && now.getMinutes() < 30)
                            || (now.getHours() === slot.h && slot.m === 30 && now.getMinutes() >= 30);
        const nextSlot = TIME_SLOTS[idx + 1];
        const nextLabel = nextSlot ? nextSlot.label : '24:00';
        const isFirst = (idx === 0);
        const isLast = (idx === TIME_SLOTS.length - 1);

        thead += `<th class="cal-th-time${isHour ? ' hour-mark' : ''}${isCurrentHour ? ' current-hour' : ''}" data-idx="${idx}" title="Khung giờ: ${slot.label} – ${nextLabel}">
                    <div class="cal-ruler-tick${isFirst ? ' first-tick' : ''}">
                        <span class="ruler-label">${slot.label}</span>
                        <span class="ruler-notch"></span>
                    </div>
                    ${isLast ? `
                    <div class="cal-ruler-tick last-tick">
                        <span class="ruler-label">24:00</span>
                        <span class="ruler-notch"></span>
                    </div>` : ''}
                  </th>`;
    });
    thead += `</tr></thead>`;

    // Body rows
    let tbody = `<tbody>`;
    visibleCourts.forEach(court => {
        const { eventMap, slotsForCourt } = buildEventMapForCourt(court.id);

        tbody += `<tr>`;
        // Court label
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
                if (ev.startIdx === i) {
                    let tdClass = 'cal-td-slot ';
                    let blockClass = 'cal-slot-block ';
                    if (ev.type === 'booked')       { tdClass += 'cal-td-booked';      blockClass += 'block-booked'; }
                    if (ev.type === 'maintenance')  { tdClass += 'cal-td-maintenance'; blockClass += 'block-maintenance'; }
                    if (ev.type === 'locked')       { tdClass += 'cal-td-locked';      blockClass += 'block-locked'; }
                    if (ev.type === 'event')        { tdClass += 'cal-td-event';       blockClass += 'block-event'; }
                    if (ev.type === 'holding')      { tdClass += 'cal-td-holding';     blockClass += 'block-holding'; }
                    if (isHourStart) tdClass += ' hour-start';

                    const timeLabel = formatRangeShort(ev.startTime, ev.endTime);
                    const icon = ev.type === 'booked' ? 'fa-ban' 
                               : ev.type === 'maintenance' ? 'fa-screwdriver-wrench'
                               : ev.type === 'locked' ? 'fa-lock' 
                               : ev.type === 'event' ? 'fa-calendar-star' 
                               : 'fa-stopwatch';

                    tbody += `<td class="${tdClass}" colspan="${ev.span}"
                                  onclick="handleSlotClick(${court.id}, ${i}, '${ev.type}', ${ev.slotId || 0})">
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
                i++;
                continue;
            }

            // Kiểm tra xem ô giờ này có thuộc về quá khứ hay không
            const isPast = isSlotPast(activeDateStr, i);

            // Tìm slot MySQL khớp với ô này
            const matchingSlot = slotsForCourt.find(s => {
                const sIdx = timeToSlotIdx(s.start_time);
                const eIdx = timeToSlotIdx(s.end_time);
                return i >= sIdx && i < eIdx;
            });
            const slotDbId = matchingSlot ? matchingSlot.id : 0;

            // Nếu ô đã trôi qua trong quá khứ -> Khóa ô, không cho phép đặt
            if (isPast) {
                const nextSlot = TIME_SLOTS[i + 1];
                const nextLabel = nextSlot ? nextSlot.label : '24:00';
                tbody += `<td class="cal-td-slot cal-td-past${isHourStart ? ' hour-start' : ''}"
                                data-court="${court.id}" data-slot="${i}"
                                title="Khung giờ ${slot.label} – ${nextLabel} đã qua (không thể đặt)"
                                onclick="handlePastSlotClick('${slot.label} – ${nextLabel}')">
                                <div class="past-label">Đã qua</div>
                          </td>`;
                i++;
                continue;
            }

            // Kiểm tra xem ô này có đang được người dùng chọn không
            const isSelected = (selectedCourtId === court.id && selectedSlotIndices.includes(i));
            if (isSelected) {
                const minIdx = Math.min(...selectedSlotIndices);
                if (i === minIdx) {
                    const span = selectedSlotIndices.length;
                    const startTime = TIME_SLOTS[minIdx].label;
                    const endTime = (minIdx + span < TIME_SLOTS.length) ? TIME_SLOTS[minIdx + span].label : '24:00';
                    const timeLabel = formatRangeShort(startTime, endTime);
                    const durText = span === 1 ? '30 phút' : (span === 2 ? '1 tiếng' : (span === 3 ? '1h30p' : `${span * 0.5} tiếng`));

                    tbody += `<td class="cal-td-slot cal-td-holding hour-start" colspan="${span}"
                                  onclick="confirmHoldSelectedSlots()">
                                <div class="cal-slot-block block-holding" style="background:#0d9488 !important; color:#fff;" title="Bấm để khóa giữ chỗ 5 phút">
                                    <div class="block-time-label">
                                        <i class="fa-solid fa-stopwatch me-1" style="font-size:0.6rem;"></i>${timeLabel}
                                    </div>
                                    <div class="block-title" style="font-weight:800; font-size:0.75rem;">Đang chọn (${durText})</div>
                                </div>
                              </td>`;
                    i += span;
                    continue;
                } else {
                    i++;
                    continue;
                }
            }

            const nextSlot = TIME_SLOTS[i + 1];
            const nextLabel = nextSlot ? nextSlot.label : '24:00';
            tbody += `<td class="cal-td-slot s-available${isHourStart ? ' hour-start' : ''}"
                            data-court="${court.id}" data-slot="${i}" data-slotid="${slotDbId}"
                            title="Khung giờ ${slot.label} – ${nextLabel} (1 ô = 30 phút). Bấm để chọn."
                            onclick="handleSlotClick(${court.id}, ${i}, 'available', ${slotDbId})"
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

/* -------- Click handler: Cho phép chọn từ 1 tiếng (2 ô) hoặc 1.5 tiếng (3 ô) trở lên -------- */
async function handleSlotClick(courtIndex, slotIdx, type, slotId) {
    // 1. Chặn click vào bất kỳ slot nào đã thuộc về quá khứ
    if (isSlotPast(activeDateStr, slotIdx)) {
        handlePastSlotClick(TIME_SLOTS[slotIdx]?.label || '');
        return;
    }

    if (type === 'maintenance') {
        Swal.fire({
            icon: 'warning',
            title: 'Sân đang bảo trì',
            html: `<p class="mb-1">Khung giờ này đang được chủ sân tạm ngưng nhận khách để bảo trì & sửa chữa mặt thảm.</p><p class="mt-2 mb-0 fw-semibold text-teal small">Vui lòng chọn các khung giờ khả dụng khác!</p>`,
            confirmButtonColor: '#ea580c',
            confirmButtonText: 'Đã hiểu'
        });
        return;
    }

    if (type === 'booked' || type === 'locked') {
        const slot = serverSlots.find(s => s.id === slotId);
        const isBlocked = slot && slot.status === 'blocked';
        Swal.fire({
            icon: isBlocked ? 'info' : (type === 'locked' ? 'info' : 'error'),
            title: isBlocked ? 'Khung giờ tạm khóa' : (type === 'locked' ? 'Khung giờ đang có khách giữ' : 'Khung giờ đã được đặt'),
            text: isBlocked
                ? 'Khung giờ này đang được chủ sân tạm khóa ngừng nhận đặt. Vui lòng chọn các khung giờ khác.'
                : (type === 'locked'
                    ? 'Khung giờ này đang có khách khác giữ chỗ để thanh toán cọc (5 phút). Vui lòng chọn các ô trống khác.'
                    : 'Khung giờ này đã được thanh toán và chốt lịch. Vui lòng chọn các ô trống khác.'),
            confirmButtonColor: '#0d9488'
        });
        return;
    }

    if (type === 'holding') {
        // Khách click vào ô mà chính mình đang giữ
        const heldSlot = serverSlots.find(s => s.id === slotId);
        const minsLeft = heldSlot ? Math.floor(heldSlot.hold_seconds_left / 60) : 4;
        const secsLeft = heldSlot ? (heldSlot.hold_seconds_left % 60) : 59;

        const res = await Swal.fire({
            icon: 'success',
            title: 'Bạn đang giữ chỗ này!',
            html: `<p class="mb-2">Bạn còn <strong>${minsLeft} phút ${secsLeft} giây</strong> để hoàn tất thanh toán cọc.</p>
                   <p class="small text-muted mb-0">Nhấn <strong>Thanh toán ngay</strong> để tiếp tục hoặc <strong>Hủy giữ chỗ</strong> để mở lại ô.</p>`,
            confirmButtonText: '<i class="fa-solid fa-lock me-1"></i> Thanh toán ngay',
            confirmButtonColor: '#0d9488',
            showCancelButton: true,
            cancelButtonText: 'Hủy giữ chỗ',
            cancelButtonColor: '#ef4444'
        });

        if (res.isConfirmed) {
            const heldIds = myHeldSlots.map(s => s.id).join(',');
            window.location.href = `confirm.php?court_id=${activeCourtId}&slot_ids=${heldIds}&date=${activeDateStr}`;
        } else if (res.dismiss === Swal.DismissReason.cancel) {
            await releaseHoldSlots(myHeldSlots.map(s => s.id));
        }
        return;
    }

    // TYPE === 'available': Cho phép đặt từ 30 phút (1 ô), 1 tiếng (2 ô), 1.5 tiếng (3 ô) trở lên
    const courtName = COURTS[courtIndex]?.name;
    const slotsForCourt = serverSlots.filter(s => s.sub_court_name === courtName);

    // Trường hợp 1: Nếu chưa chọn ô nào hoặc đang click sang thảm khác -> Khởi tạo vùng chọn 30 phút (1 ô)
    if (selectedCourtId !== courtIndex || selectedSlotIndices.length === 0) {
        selectedCourtId = courtIndex;
        selectedSlotIndices = [slotIdx];
    } 
    // Trường hợp 2: Đang chọn trên cùng thảm -> Cho phép mở rộng / thu hẹp / bỏ chọn
    else {
        const minIdx = Math.min(...selectedSlotIndices);
        const maxIdx = Math.max(...selectedSlotIndices);

        // Nếu click vào chính ô duy nhất đang chọn -> Bỏ chọn
        if (selectedSlotIndices.length === 1 && selectedSlotIndices[0] === slotIdx) {
            selectedCourtId = null;
            selectedSlotIndices = [];
        }
        // Nếu click vào ô ngay sau maxIdx: mở rộng thêm 30 phút (thành 1 tiếng, 1.5 tiếng, 2 tiếng...)
        else if (slotIdx === maxIdx + 1) {
            if (isCellAvailable(slotsForCourt, slotIdx)) {
                selectedSlotIndices.push(slotIdx);
                selectedSlotIndices.sort((a, b) => a - b);
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Khung giờ tiếp theo không khả dụng',
                    text: 'Ô bạn vừa chọn đã có khách đặt hoặc đang được giữ chỗ.',
                    confirmButtonColor: '#0d9488'
                });
                return;
            }
        }
        // Nếu click vào ô ngay trước minIdx: mở rộng thêm 30 phút về phía trước
        else if (slotIdx === minIdx - 1) {
            if (isCellAvailable(slotsForCourt, slotIdx)) {
                selectedSlotIndices.push(slotIdx);
                selectedSlotIndices.sort((a, b) => a - b);
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Khung giờ phía trước không khả dụng',
                    text: 'Ô bạn vừa chọn đã có khách đặt hoặc đang được giữ chỗ.',
                    confirmButtonColor: '#0d9488'
                });
                return;
            }
        }
        // Nếu click vào ô đuôi (maxIdx) khi đang chọn từ 2 ô trở lên: co ngắn lại bớt 30 phút
        else if (slotIdx === maxIdx && selectedSlotIndices.length > 1) {
            selectedSlotIndices = selectedSlotIndices.filter(idx => idx !== maxIdx);
        }
        // Nếu click vào ô đầu (minIdx) khi đang chọn từ 2 ô trở lên: co ngắn lại từ đầu
        else if (slotIdx === minIdx && selectedSlotIndices.length > 1) {
            selectedSlotIndices = selectedSlotIndices.filter(idx => idx !== minIdx);
        }
        // Nếu click vào ô ngoài dải liền kề: chuyển vùng chọn sang ô mới (30 phút)
        else {
            selectedCourtId = courtIndex;
            selectedSlotIndices = [slotIdx];
        }
    }

    renderCalendar(getVisibleCourts());
    updateSummaryBar();
}

/* -------- Kiểm tra 1 slotIdx có available không -------- */
function isCellAvailable(slotsForCourt, idx) {
    if (idx < 0 || idx >= TIME_SLOTS.length) return false;
    // Chặn nếu slot này đã thuộc về quá khứ
    if (isSlotPast(activeDateStr, idx)) return false;

    const targetSlot = slotsForCourt.find(s => {
        const sIdx = timeToSlotIdx(s.start_time);
        const eIdx = timeToSlotIdx(s.end_time);
        return idx >= sIdx && idx < eIdx;
    });
    return targetSlot && targetSlot.status === 'available' && !targetSlot.is_past;
}

/* -------- Lấy danh sách slot ID trong MySQL tương ứng với selectedSlotIndices -------- */
function getSelectedDbSlotIds() {
    if (selectedCourtId === null || selectedSlotIndices.length === 0) return [];
    const courtName = COURTS[selectedCourtId]?.name;
    const slotsForCourt = serverSlots.filter(s => s.sub_court_name === courtName);

    const ids = [];
    selectedSlotIndices.forEach(idx => {
        const s = slotsForCourt.find(slot => {
            const sIdx = timeToSlotIdx(slot.start_time);
            const eIdx = timeToSlotIdx(slot.end_time);
            return idx >= sIdx && idx < eIdx;
        });
        if (s && !ids.includes(s.id)) {
            ids.push(s.id);
        }
    });
    return ids;
}

/* -------- Thực hiện Giữ Chỗ (Hold) cho các ô đã chọn (30p, 1h, 1.5h, 2h...) -------- */
async function confirmHoldSelectedSlots() {
    // 1. Kiểm tra nếu vùng chọn chứa bất kỳ slot nào đã trôi qua
    const hasPast = selectedSlotIndices.some(idx => isSlotPast(activeDateStr, idx));
    if (hasPast) {
        Swal.fire({
            icon: 'warning',
            title: 'Khung giờ đã trôi qua',
            text: 'Một hoặc nhiều khung giờ bạn chọn đã trôi qua trong quá khứ. Vui lòng chọn các khung giờ sắp tới!',
            confirmButtonColor: '#0d9488'
        });
        selectedCourtId = null;
        selectedSlotIndices = [];
        renderCalendar(getVisibleCourts());
        updateSummaryBar();
        return;
    }

    const slotIds = getSelectedDbSlotIds();
    if (slotIds.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Chưa chọn khung giờ', text: 'Vui lòng chọn ít nhất 1 khung giờ (30 phút)!', confirmButtonColor: '#0d9488' });
        return;
    }

    const hasInvalid = slotIds.some(id => {
        const s = serverSlots.find(x => x.id === id);
        return s && (s.status === 'maintenance' || s.status === 'blocked');
    });
    if (hasInvalid) {
        Swal.fire({
            icon: 'warning',
            title: 'Khung giờ không khả dụng',
            text: 'Một hoặc nhiều khung giờ bạn chọn đang trong thời gian bảo trì sân hoặc tạm khóa. Vui lòng chọn lại!',
            confirmButtonColor: '#ea580c'
        });
        selectedCourtId = null;
        selectedSlotIndices = [];
        renderCalendar(getVisibleCourts());
        updateSummaryBar();
        return;
    }

    Swal.fire({
        title: 'Đang giữ chỗ...',
        text: 'Hệ thống đang khóa các khung giờ đã chọn cho bạn...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await fetch('api/hold.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ slot_ids: slotIds, action: 'hold' })
        });
        const result = await response.json();

        if (response.status === 200 && result.status === 'success') {
            Swal.close();
            // Reset vùng chọn tạm vì đã được hold chính thức trong DB
            selectedCourtId = null;
            selectedSlotIndices = [];

            await fetchSlots(activeCourtId, activeDateStr);

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Giữ chỗ thành công 5 phút!',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
        } else if (response.status === 401 || result.code === 401) {
            Swal.fire({
                icon: 'warning',
                title: 'Yêu cầu đăng nhập',
                text: 'Bạn cần đăng nhập tài khoản để giữ chỗ và đặt sân trực tuyến.',
                confirmButtonText: 'Đăng nhập ngay',
                confirmButtonColor: '#0d9488',
                showCancelButton: true,
                cancelButtonText: 'Để sau'
            }).then(r => {
                if (r.isConfirmed) {
                    window.location.href = `auth.php?redirect=booking.php?court_id=${activeCourtId}&date=${activeDateStr}`;
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Không thể giữ chỗ',
                text: result.message || 'Một trong các khung giờ bạn chọn vừa có người khác giữ chỗ.',
                confirmButtonColor: '#0d9488'
            });
            await fetchSlots(activeCourtId, activeDateStr);
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối tới máy chủ.', confirmButtonColor: '#0d9488' });
    }
}

/* -------- Hủy giữ chỗ (Release) -------- */
async function releaseHoldSlots(slotIds) {
    try {
        const response = await fetch('api/hold.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ slot_ids: slotIds, action: 'release' })
        });
        const result = await response.json();
        if (result.status === 'success') {
            myHeldSlots = [];
            await fetchSlots(activeCourtId, activeDateStr);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Đã hủy giữ chỗ.',
                showConfirmButton: false,
                timer: 2000
            });
        }
    } catch (err) {
        console.error('Lỗi khi hủy giữ chỗ:', err);
    }
}

/* -------- Cập nhật thanh Summary Bar ở chân trang -------- */
function updateSummaryBar() {
    const bar = document.getElementById('bookingSummaryBar');
    if (!bar) return;

    // Trường hợp 1: Đang có slot HOLDING thật trong DB
    if (myHeldSlots.length > 0) {
        bar.classList.remove('d-none');
        bar.style.display = 'flex';
        const courtName = myHeldSlots[0].sub_court_name;
        const startTime = myHeldSlots[0].start_time;
        const endTime   = myHeldSlots[myHeldSlots.length - 1].end_time;
        const durationHours = myHeldSlots.length * 0.5;
        const durationLabel = durationHours === 0.5 ? '30 phút' : (durationHours === 1 ? '1 tiếng' : (durationHours === 1.5 ? '1 tiếng 30 phút' : `${durationHours} tiếng`));

        let totalPrice = 0;
        myHeldSlots.forEach(s => { totalPrice += (s.price || 50000); });
        const deposit = Math.round(totalPrice * 0.5);

        const totalSecs = Math.max(0, Math.min(300, myHeldSlots[0].hold_seconds_left || 0));
        const mins = Math.floor(totalSecs / 60);
        const secs = totalSecs % 60;

        const titleEl = document.getElementById('summaryTitle');
        const detailEl = document.getElementById('summaryDetail');
        if (titleEl) {
            titleEl.textContent = `${courtName} ${formatRangeShort(startTime, endTime)}`;
        }
        if (detailEl) {
            detailEl.innerHTML = `<span>${formatMoney(totalPrice)}</span> <span class="opacity-75">•</span> <span>Đặt cọc ${formatMoney(deposit)}</span> <span class="badge bg-warning text-dark ms-1"><i class="fa-regular fa-clock me-1"></i>Còn ${mins}p${secs < 10 ? '0' : ''}${secs}s</span>`;
        }

        const payBtn = document.getElementById('btnGoPayment');
        if (payBtn) {
            const heldIds = myHeldSlots.map(s => s.id).join(',');
            payBtn.href = `confirm.php?court_id=${activeCourtId}&slot_ids=${heldIds}&date=${activeDateStr}`;
            payBtn.innerHTML = '<i class="fa-solid fa-credit-card me-2"></i>Thanh Toán';
            payBtn.onclick = null;
        }

        const cancelBtn = document.getElementById('btnCancelSel');
        if (cancelBtn) {
            cancelBtn.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>Huỷ giữ chỗ';
            cancelBtn.onclick = () => releaseHoldSlots(myHeldSlots.map(s => s.id));
        }
        return;
    }

    // Trường hợp 2: Đang CHỌN Ô TRỐNG TRÊN LƯỚI (chưa hold)
    if (selectedCourtId !== null && selectedSlotIndices.length >= 1) {
        bar.classList.remove('d-none');
        bar.style.display = 'flex';
        const court = COURTS[selectedCourtId];
        const minIdx = Math.min(...selectedSlotIndices);
        const maxIdx = Math.max(...selectedSlotIndices);
        const startTime = TIME_SLOTS[minIdx].label;
        const endTime   = (maxIdx + 1 < TIME_SLOTS.length) ? TIME_SLOTS[maxIdx + 1].label : '24:00';
        const countCells = selectedSlotIndices.length;
        const durationHours = countCells * 0.5;
        const durationLabel = durationHours === 0.5 ? '30 phút' : (durationHours === 1 ? '1 tiếng' : (durationHours === 1.5 ? '1 tiếng 30 phút' : `${durationHours} tiếng`));

        // Tính tiền ước tính dựa theo serverSlots thật của thảm này
        const courtSlots = serverSlots.filter(s => s.sub_court_name === court.name);
        let totalPrice = 0;
        selectedSlotIndices.forEach(idx => {
            const matched = courtSlots.find(s => {
                const sIdx = timeToSlotIdx(s.start_time);
                const eIdx = timeToSlotIdx(s.end_time);
                return idx >= sIdx && idx < eIdx;
            });
            if (matched && matched.price) {
                totalPrice += matched.price;
            } else {
                totalPrice += 50000;
            }
        });
        const deposit = Math.round(totalPrice * 0.5);

        const titleEl = document.getElementById('summaryTitle');
        const detailEl = document.getElementById('summaryDetail');
        if (titleEl) {
            titleEl.textContent = `${court.name} ${formatRangeShort(startTime, endTime)}`;
        }
        if (detailEl) {
            detailEl.innerHTML = `<span>${formatMoney(totalPrice)}</span> <span class="opacity-75">•</span> <span>Đặt cọc ${formatMoney(deposit)}</span> <span class="badge bg-info text-dark ms-1"><i class="fa-solid fa-hand-pointer me-1"></i>Đang chọn ${countCells} ô (${durationLabel})</span>`;
        }

        const payBtn = document.getElementById('btnGoPayment');
        if (payBtn) {
            payBtn.removeAttribute('href');
            payBtn.style.cursor = 'pointer';
            payBtn.innerHTML = '<i class="fa-solid fa-stopwatch me-2"></i>Khóa Giữ Chỗ 5 Phút';
            payBtn.onclick = (e) => {
                e.preventDefault();
                confirmHoldSelectedSlots();
            };
        }

        const cancelBtn = document.getElementById('btnCancelSel');
        if (cancelBtn) {
            cancelBtn.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>Bỏ chọn';
            cancelBtn.onclick = () => {
                selectedCourtId = null;
                selectedSlotIndices = [];
                renderCalendar(getVisibleCourts());
                updateSummaryBar();
            };
        }
        return;
    }

    // Không có gì được chọn và không có slot holding
    bar.classList.add('d-none');
    bar.style.display = 'none';
}

/* -------- Countdown từng giây cho các slot đang giữ -------- */
function startCountdownTick() {
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
        if (myHeldSlots.length > 0 && myHeldSlots[0].hold_seconds_left > 0) {
            myHeldSlots.forEach(s => { s.hold_seconds_left--; });
            const totalSecs = Math.max(0, Math.min(300, myHeldSlots[0].hold_seconds_left));
            const mins = Math.floor(totalSecs / 60);
            const secs = totalSecs % 60;
            const timeStr = `Còn ${mins}p${secs < 10 ? '0' : ''}${secs}s`;

            // Cập nhật text trên các ô đang giữ trong bảng
            document.querySelectorAll('.block-holding .block-sub').forEach(el => {
                el.textContent = timeStr;
            });

            // Cập nhật text trên summary bar
            const detailEl = document.getElementById('summaryDetail');
            if (detailEl) {
                let totalPrice = 0;
                myHeldSlots.forEach(s => { totalPrice += (s.price || 50000); });
                const deposit = Math.round(totalPrice * 0.5);
                detailEl.innerHTML = `<span>${formatMoney(totalPrice)}</span> <span class="opacity-75">•</span> <span>Đặt cọc ${formatMoney(deposit)}</span> <span class="badge bg-warning text-dark ms-1"><i class="fa-regular fa-clock me-1"></i>${timeStr}</span>`;
            }
        } else if (myHeldSlots.length > 0 && myHeldSlots[0].hold_seconds_left <= 0) {
            myHeldSlots = [];
            fetchSlots(activeCourtId, activeDateStr);
        }
    }, 1000);
}

function highlightCell(el) { el.classList.add('hover-highlight'); }
function unhighlightCell(el) { el.classList.remove('hover-highlight'); }

/* -------- Date helpers -------- */
function formatDateLocal(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/* -------- Date pills navigation -------- */
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
    if (!row) return;

    const today = new Date(); today.setHours(0,0,0,0);

    const label = document.getElementById('weekRangeLabel');
    const fmt = d => `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}`;
    if (label) {
        label.textContent = `${fmt(dates[0])} – ${fmt(dates[6])}/${dates[6].getFullYear()}`;
    }

    row.innerHTML = dates.map(d => {
        const dStr = formatDateLocal(d);
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
    const dateInput = document.getElementById('bookingDateInput');
    if (dateInput) dateInput.value = dateStr;

    // Reset vùng chọn tạm khi đổi ngày
    selectedCourtId = null;
    selectedSlotIndices = [];

    renderDatePills(currentWeekStart);
    fetchSlots(activeCourtId, activeDateStr);
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

/* -------- Polling Engine: Tự động chạy mỗi 5 giây -------- */
function startPolling() {
    if (pollingTimer) clearInterval(pollingTimer);
    pollingTimer = setInterval(() => {
        fetchSlots(activeCourtId, activeDateStr, true);
    }, 5000);
}

/* -------- Init on Page Load -------- */
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    activeCourtId = Number(urlParams.get('court_id'));
    if (!Number.isSafeInteger(activeCourtId) || activeCourtId<=0) {window.location.replace('courts.php');return;}

    const today = new Date(); today.setHours(0,0,0,0);
    currentWeekStart = getMondayOf(today);
    activeDateStr = urlParams.get('date') || formatDateLocal(today);

    const dateInput = document.getElementById('bookingDateInput');
    if (dateInput) dateInput.value = activeDateStr;

    renderDatePills(currentWeekStart);
    initScrollArrows();

    // 1. Nạp slots từ MySQL
    fetchSlots(activeCourtId, activeDateStr);

    // 2. Bắt đầu Polling 5s & Countdown từng giây
    startPolling();
    startCountdownTick();

    // Week navigation
    document.getElementById('btnPrevWeek')?.addEventListener('click', () => {
        currentWeekStart = new Date(currentWeekStart);
        currentWeekStart.setDate(currentWeekStart.getDate() - 7);
        renderDatePills(currentWeekStart);
    });
    document.getElementById('btnNextWeek')?.addEventListener('click', () => {
        currentWeekStart = new Date(currentWeekStart);
        currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        renderDatePills(currentWeekStart);
    });

    // Date input change
    document.getElementById('bookingDateInput')?.addEventListener('change', e => {
        selectDate(e.target.value);
        currentWeekStart = getMondayOf(new Date(e.target.value + 'T00:00:00'));
        renderDatePills(currentWeekStart);
    });

    // Court filter tabs (Tất cả / Thảm A1 / Thảm A2...)
    document.querySelectorAll('.court-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.court-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            renderCalendar(getVisibleCourts());
        });
    });

    // Scroll to current time
    const container = document.getElementById('calScrollContainer');
    const now = new Date();
    const nowMins = now.getHours() * 60 + now.getMinutes() - (START_HOUR * 60 + START_MIN);
    const nowSlotIdx = Math.max(0, Math.floor(nowMins / SLOT_MINS) - 2);
    const slotW = 72;
    const courtLabelW = 100;
    if (nowSlotIdx > 0 && container) {
        setTimeout(() => { container.scrollLeft = courtLabelW + nowSlotIdx * slotW; }, 300);
    }
});