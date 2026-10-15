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

/* -------- State -------- */
let activeCourtId = 1;          // ID sân cha từ MySQL (ví dụ: Lê Thánh Tôn Sport = 1)
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

/* -------- Convert Time to Slot Index -------- */
function timeToSlotIdx(timeStr) {
    if (!timeStr) return -1;
    const [h, m] = timeStr.split(':').map(Number);
    const totalMins = h * 60 + m;
    const startMins = START_HOUR * 60 + START_MIN;
    if (totalMins < startMins) return 0;
    return Math.floor((totalMins - startMins) / SLOT_MINS);
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
        } else if (s.status === 'blocked') {
            type = 'locked';
        } else if (s.status === 'holding') {
            type = s.is_my_hold ? 'holding' : 'locked';
        }

        let startIdx = timeToSlotIdx(s.start_time);
        let endIdx   = timeToSlotIdx(s.end_time);
        let mergedSlots = [s];

        // Gộp các slot liền kề cùng loại holding của chính user hoặc cùng booking
        let j = i + 1;
        while (j < slotsForCourt.length) {
            const next = slotsForCourt[j];
            const nextStart = timeToSlotIdx(next.start_time);
            const nextEnd   = timeToSlotIdx(next.end_time);

            let canMerge = false;
            if (nextStart === endIdx) {
                if (type === 'holding' && next.status === 'holding' && next.is_my_hold) {
                    canMerge = true;
                } else if (type === 'locked' && next.status === 'holding' && !next.is_my_hold && next.held_by === s.held_by) {
                    canMerge = true;
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

        if (type === 'booked') {
            label = 'Đã đặt';
            sub = `${s.start_time}–${s.end_time}`;
        } else if (type === 'holding') {
            const totalSecs = Math.max(0, Math.min(300, s.hold_seconds_left || 0));
            const mins = Math.floor(totalSecs / 60);
            const secs = totalSecs % 60;
            const hours = mergedSlots.length * 0.5;
            const durLabel = hours === 1 ? '1 tiếng' : (hours === 1.5 ? '1 tiếng 30 phút' : `${hours} tiếng`);
            label = `Bạn đang giữ (${durLabel})`;
            sub = `Còn ${mins}p${secs < 10 ? '0' : ''}${secs}s`;
        } else if (type === 'locked') {
            label = s.status === 'blocked' ? 'Ngừng nhận' : 'Đang giữ';
            sub = s.status === 'blocked' ? `${s.start_time}–${s.end_time}` : 'Khách đang cọc';
        }

        const ev = {
            slotId: s.id,
            slotIds: mergedSlots.map(x => x.id),
            type: type,
            startIdx: startIdx,
            span: span,
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
        thead += `<th class="cal-th-time${isHour ? ' hour-mark' : ''}${isCurrentHour ? ' current-hour' : ''}" data-idx="${idx}">
                    ${slot.label}
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
                    if (ev.type === 'booked')   { tdClass += 'cal-td-booked';  blockClass += 'block-booked'; }
                    if (ev.type === 'locked')   { tdClass += 'cal-td-locked';  blockClass += 'block-locked'; }
                    if (ev.type === 'event')    { tdClass += 'cal-td-event';   blockClass += 'block-event'; }
                    if (ev.type === 'holding')  { tdClass += 'cal-td-holding'; blockClass += 'block-holding'; }
                    if (isHourStart) tdClass += ' hour-start';

                    const timeLabel = `${TIME_SLOTS[ev.startIdx].label} – ${TIME_SLOTS[Math.min(ev.startIdx + ev.span, TIME_SLOTS.length - 1)].label}`;
                    const icon = ev.type === 'booked' ? 'fa-ban' : ev.type === 'locked' ? 'fa-lock' : ev.type === 'event' ? 'fa-calendar-star' : 'fa-stopwatch';

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

            // Tìm slot MySQL khớp với ô này
            const matchingSlot = slotsForCourt.find(s => {
                const sIdx = timeToSlotIdx(s.start_time);
                const eIdx = timeToSlotIdx(s.end_time);
                return i >= sIdx && i < eIdx;
            });
            const slotDbId = matchingSlot ? matchingSlot.id : 0;

            // Kiểm tra xem ô này có đang được người dùng chọn không
            const isSelected = (selectedCourtId === court.id && selectedSlotIndices.includes(i));
            if (isSelected) {
                const minIdx = Math.min(...selectedSlotIndices);
                if (i === minIdx) {
                    const span = selectedSlotIndices.length;
                    const startTime = TIME_SLOTS[minIdx].label;
                    const endTime = TIME_SLOTS[Math.min(minIdx + span, TIME_SLOTS.length - 1)].label;
                    const timeLabel = `${startTime} – ${endTime}`;
                    const hours = span * 0.5;
                    const durLabel = hours === 1 ? '1 tiếng' : (hours === 1.5 ? '1 tiếng 30 phút' : `${hours} tiếng`);

                    tbody += `<td class="cal-td-slot cal-td-holding hour-start" colspan="${span}"
                                  onclick="confirmHoldSelectedSlots()">
                                <div class="cal-slot-block block-holding" style="background:#0d9488 !important; color:#fff;" title="Bấm để khóa giữ chỗ 5 phút">
                                    <div class="block-time-label">
                                        <i class="fa-solid fa-stopwatch me-1" style="font-size:0.6rem;"></i>${timeLabel}
                                    </div>
                                    <div class="block-title" style="font-weight:800; font-size:0.75rem;">Đang chọn</div>
                                    <div class="block-sub" style="font-size:0.65rem; opacity:0.95;">
                                        <i class="fa-solid fa-lock me-1"></i>Khóa giữ chỗ (${durLabel})
                                    </div>
                                </div>
                              </td>`;
                    i += span;
                    continue;
                } else {
                    i++;
                    continue;
                }
            }

            tbody += `<td class="cal-td-slot s-available${isHourStart ? ' hour-start' : ''}"
                            data-court="${court.id}" data-slot="${i}" data-slotid="${slotDbId}"
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
    if (type === 'booked' || type === 'locked') {
        Swal.fire({
            icon: type === 'locked' ? 'info' : 'error',
            title: type === 'locked' ? 'Khung giờ đang có khách giữ' : 'Khung giờ đã được đặt',
            text: type === 'locked'
                ? 'Khung giờ này đang có khách khác giữ chỗ để thanh toán cọc (5 phút). Vui lòng chọn các ô trống khác.'
                : 'Khung giờ này đã được thanh toán và chốt lịch. Vui lòng chọn các ô trống khác.',
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

    // TYPE === 'available': Chọn khung giờ từ 1 tiếng (2 ô) / 1.5 tiếng (3 ô) trở lên
    const courtName = COURTS[courtIndex]?.name;
    const slotsForCourt = serverSlots.filter(s => s.sub_court_name === courtName);

    // Trường hợp 1: Nếu chưa chọn ô nào hoặc đang click sang thảm khác -> Khởi tạo vùng chọn TỐI THIỂU 1 TIẾNG (2 ô)
    if (selectedCourtId !== courtIndex || selectedSlotIndices.length === 0) {
        // Kiểm tra xem ô hiện tại và ô kế tiếp có trống không
        const nextIdx = slotIdx + 1;
        const isNextAvailable = isCellAvailable(slotsForCourt, nextIdx);

        if (!isNextAvailable) {
            // Nếu ô kế tiếp bị bận, thử kiểm tra ô phía trước
            const prevIdx = slotIdx - 1;
            const isPrevAvailable = isCellAvailable(slotsForCourt, prevIdx);
            if (isPrevAvailable) {
                selectedCourtId = courtIndex;
                selectedSlotIndices = [prevIdx, slotIdx];
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thời gian đặt tối thiểu là 1 tiếng',
                    text: 'Quy định đặt sân tối thiểu là 1 tiếng (2 ô 30 phút liên tiếp). Khung giờ liền kề ô này đã bận, vui lòng chọn vị trí khác!',
                    confirmButtonColor: '#0d9488'
                });
                return;
            }
        } else {
            selectedCourtId = courtIndex;
            selectedSlotIndices = [slotIdx, nextIdx]; // Mặc định chọn 1 tiếng (2 ô)
        }
    } 
    // Trường hợp 2: Đang chọn trên cùng thảm -> Cho phép mở rộng thành 1.5 tiếng (3 ô), 2 tiếng (4 ô)...
    else {
        const minIdx = Math.min(...selectedSlotIndices);
        const maxIdx = Math.max(...selectedSlotIndices);

        // Nếu click vào ô ngay sau maxIdx: mở rộng thêm 30 phút (thành 1.5 tiếng, 2 tiếng...)
        if (slotIdx === maxIdx + 1) {
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
        // Nếu click vào ô đuôi (maxIdx): cho phép co ngắn lại bớt 30 phút (nhưng không dưới 2 ô = 1 tiếng)
        else if (slotIdx === maxIdx) {
            if (selectedSlotIndices.length > 2) {
                selectedSlotIndices = selectedSlotIndices.filter(idx => idx !== maxIdx);
            } else {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Thời gian tối thiểu là 1 tiếng (2 ô liên tiếp)',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }
        // Nếu click vào ô ngoài dải liền kề: reset và bắt đầu chọn vùng 1 tiếng mới từ ô đó
        else {
            const nextIdx = slotIdx + 1;
            if (isCellAvailable(slotsForCourt, nextIdx)) {
                selectedCourtId = courtIndex;
                selectedSlotIndices = [slotIdx, nextIdx];
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thời gian đặt tối thiểu là 1 tiếng',
                    text: 'Quy định đặt sân tối thiểu là 1 tiếng (2 ô 30 phút liên tiếp). Vui lòng chọn vị trí có 2 ô trống liền kề!',
                    confirmButtonColor: '#0d9488'
                });
                return;
            }
        }
    }

    renderCalendar(getVisibleCourts());
    updateSummaryBar();
}

/* -------- Kiểm tra 1 slotIdx có available không -------- */
function isCellAvailable(slotsForCourt, idx) {
    if (idx < 0 || idx >= TIME_SLOTS.length) return false;
    const targetSlot = slotsForCourt.find(s => {
        const sIdx = timeToSlotIdx(s.start_time);
        const eIdx = timeToSlotIdx(s.end_time);
        return idx >= sIdx && idx < eIdx;
    });
    return targetSlot && targetSlot.status === 'available';
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

/* -------- Thực hiện Giữ Chỗ (Hold) cho các ô đã chọn (1h, 1.5h, 2h...) -------- */
async function confirmHoldSelectedSlots() {
    const slotIds = getSelectedDbSlotIds();
    if (slotIds.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Chưa chọn khung giờ', text: 'Vui lòng chọn ít nhất 1 tiếng (2 ô liên tiếp)!', confirmButtonColor: '#0d9488' });
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
        const durationLabel = durationHours === 1 ? '1 tiếng' : (durationHours === 1.5 ? '1 tiếng 30 phút' : `${durationHours} tiếng`);

        let totalPrice = 0;
        myHeldSlots.forEach(s => { totalPrice += (s.price || 50000); });
        const deposit = Math.round(totalPrice * 0.5);

        const totalSecs = Math.max(0, Math.min(300, myHeldSlots[0].hold_seconds_left || 0));
        const mins = Math.floor(totalSecs / 60);
        const secs = totalSecs % 60;

        const titleEl = document.getElementById('summaryTitle');
        const detailEl = document.getElementById('summaryDetail');
        if (titleEl) {
            titleEl.textContent = `${courtName} • ${startTime} – ${endTime} (${durationLabel})`;
        }
        if (detailEl) {
            detailEl.innerHTML = `${formatMoney(totalPrice)} • Đặt cọc ${formatMoney(deposit)} • <span class="badge bg-warning text-dark"><i class="fa-regular fa-clock me-1"></i>Còn ${mins}p${secs < 10 ? '0' : ''}${secs}s</span>`;
        }

        const payBtn = document.getElementById('btnGoPayment');
        if (payBtn) {
            const heldIds = myHeldSlots.map(s => s.id).join(',');
            payBtn.href = `confirm.php?court_id=${activeCourtId}&slot_ids=${heldIds}&date=${activeDateStr}`;
            payBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i>Đặt Cọc & Thanh Toán';
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
    if (selectedCourtId !== null && selectedSlotIndices.length >= 2) {
        bar.classList.remove('d-none');
        bar.style.display = 'flex';
        const court = COURTS[selectedCourtId];
        const minIdx = Math.min(...selectedSlotIndices);
        const maxIdx = Math.max(...selectedSlotIndices);
        const startTime = TIME_SLOTS[minIdx].label;
        const endTime   = TIME_SLOTS[Math.min(maxIdx + 1, TIME_SLOTS.length - 1)].label;
        const countCells = selectedSlotIndices.length;
        const durationHours = countCells * 0.5;
        const durationLabel = durationHours === 1 ? '1 tiếng' : (durationHours === 1.5 ? '1 tiếng 30 phút' : `${durationHours} tiếng`);

        // Tính tiền ước tính
        const courtPricePerHour = 100000;
        const totalPrice = Math.round(durationHours * courtPricePerHour);
        const deposit = Math.round(totalPrice * 0.5);

        const titleEl = document.getElementById('summaryTitle');
        const detailEl = document.getElementById('summaryDetail');
        if (titleEl) {
            titleEl.textContent = `${court.name} • ${startTime} – ${endTime} (${durationLabel})`;
        }
        if (detailEl) {
            detailEl.innerHTML = `${formatMoney(totalPrice)} • Đặt cọc ${formatMoney(deposit)} • <span class="badge bg-info text-dark"><i class="fa-solid fa-hand-pointer me-1"></i>Đang chọn ${countCells} ô</span>`;
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
                detailEl.innerHTML = `${formatMoney(totalPrice)} • Đặt cọc ${formatMoney(deposit)} • <span class="badge bg-warning text-dark"><i class="fa-regular fa-clock me-1"></i>${timeStr}</span>`;
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
    activeCourtId = parseInt(urlParams.get('court_id')) || 1;

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