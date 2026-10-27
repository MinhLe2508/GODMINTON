/**
 * assets/js/admin-posts.js
 * Quản trị bài viết Khám phá (Sự kiện, Gói hội viên ưu đãi, Thông báo) cho Admin.
 */

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.getElementById('postsTableBody');
    const emptyEl = document.getElementById('postsEmpty');
    const modalEl = document.getElementById('postModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const form = document.getElementById('postForm');
    const btnAdd = document.getElementById('btnAddPost');
    const searchInput = document.getElementById('searchPost');
    const filterType = document.getElementById('filterType');
    const filterStatus = document.getElementById('filterStatus');
    const paginationWrap = document.getElementById('postsPaginationWrap');
    const paginationEl = document.getElementById('postsPagination');
    const countText = document.getElementById('postsCountText');

    // Dynamic type fields
    const selectType = document.getElementById('formPostType');
    const sectionEvent = document.getElementById('sectionEventFields');
    const sectionMembership = document.getElementById('sectionMembershipFields');

    // Cover image preview & upload
    const coverFileInput = document.getElementById('formCoverFile');
    const coverUrlInput = document.getElementById('formCoverUrl');
    const coverPreviewWrap = document.getElementById('coverPreviewWrap');
    const coverPreviewImg = document.getElementById('coverPreviewImg');

    const saveBtn = document.getElementById('btnSavePost');
    const saveSpinner = document.getElementById('saveSpinner');

    let currentUser = null;
    let currentPage = 1;
    let searchDebounce = null;

    // 1. Kiểm tra xác thực Admin & render Shell
    try {
        const { user } = await BookingAccount.identity();
        if (!user || user.role !== 'admin') {
            throw new Error('Chỉ Quản trị viên (Admin) mới có quyền truy cập trang này.');
        }
        currentUser = user;
        window.__GODMINTON_USER__ = user;
        AdminApp.renderShell('posts', 'Quản lý bài viết khám phá', 'Quản trị hệ thống / Quản lý bài viết');
        loadActiveVouchers();
    } catch (e) {
        if (emptyEl) {
            emptyEl.textContent = e.message;
            emptyEl.style.display = 'block';
        }
        return;
    }

    // Tải danh sách mã giảm giá khả dụng để gắn vào bài viết
    let activeVouchers = [];
    async function loadActiveVouchers() {
        try {
            const data = await BookingAccount.request('api/admin/vouchers.php?status=active');
            activeVouchers = Array.isArray(data) ? data : [];
            const selectVoucher = document.getElementById('formPostVoucher');
            if (!selectVoucher) return;
            selectVoucher.innerHTML = '<option value="">-- Không đính kèm mã giảm giá --</option>' + 
                activeVouchers.map(v => {
                    const courtTxt = v.court_name ? ` (${v.court_name})` : ' (Toàn hệ thống)';
                    return `<option value="${v.id}">[${BookingAccount.escape(v.code)}] Giảm ${v.discount_percent}%${courtTxt}</option>`;
                }).join('');
        } catch (e) {
            console.warn('Could not load vouchers for post modal:', e);
        }
    }

    // 2. Chuyển đổi ẩn/hiện trường đặc thù theo loại bài viết
    function updateTypeFields() {
        const t = selectType.value;
        if (sectionEvent) sectionEvent.style.display = (t === 'event') ? 'block' : 'none';
        if (sectionMembership) sectionMembership.style.display = (t === 'membership') ? 'block' : 'none';
    }

    selectType.addEventListener('change', updateTypeFields);

    // 3. Xử lý xem trước ảnh và upload file ảnh
    function updatePreview(url) {
        if (url && url.trim()) {
            coverPreviewImg.src = url.trim();
            coverPreviewWrap.style.display = 'flex';
        } else {
            coverPreviewWrap.style.display = 'none';
            coverPreviewImg.src = '';
        }
    }

    coverUrlInput?.addEventListener('input', () => updatePreview(coverUrlInput.value));

    coverFileInput?.addEventListener('change', async () => {
        const file = coverFileInput.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            AdminApp.toast('error', 'Dung lượng ảnh vượt quá 5 MiB.');
            coverFileInput.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        try {
            AdminApp.toast('info', 'Đang tải ảnh lên máy chủ...');
            const csrfToken = (await BookingAccount.identity()).csrf_token || '';
            const res = await fetch('api/admin/upload-post-image.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-GODMINTON-CSRF': csrfToken },
                body: formData
            });

            const json = await res.json();
            if (!res.ok || json.status !== 'success') {
                throw new Error(json.message || 'Lỗi tải ảnh lên.');
            }

            const uploadedUrl = json.data.url;
            coverUrlInput.value = uploadedUrl;
            updatePreview(uploadedUrl);
            AdminApp.toast('success', 'Tải ảnh lên thành công!');
        } catch (err) {
            AdminApp.toast('error', err.message);
        }
    });

    // 4. Định dạng ngày giờ hiển thị
    function formatDateTime(val) {
        if (!val) return '--';
        const d = new Date(val.replace(' ', 'T'));
        if (isNaN(d.getTime())) return val;
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function toInputDateTime(val) {
        if (!val) return '';
        const d = new Date(val.replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    // 5. Tải danh sách bài viết
    async function loadPosts() {
        if (emptyEl) {
            emptyEl.style.display = 'block';
            emptyEl.textContent = 'Đang tải danh sách bài viết...';
        }
        if (tableBody) tableBody.innerHTML = '';
        if (paginationWrap) paginationWrap.style.display = 'none';

        const params = new URLSearchParams({
            type: filterType.value,
            status: filterStatus.value,
            search: searchInput.value.trim(),
            page: String(currentPage),
            limit: '15'
        });

        try {
            const data = await BookingAccount.request(`api/admin/posts.php?${params.toString()}`);
            const items = data.items || [];
            const total = data.total || 0;
            const totalPages = data.total_pages || 1;

            if (items.length === 0) {
                if (emptyEl) {
                    emptyEl.style.display = 'block';
                    emptyEl.textContent = 'Không có bài viết nào phù hợp với bộ lọc.';
                }
                return;
            }

            if (emptyEl) emptyEl.style.display = 'none';
            renderTable(items);
            renderPagination(totalPages, total);
        } catch (e) {
            if (emptyEl) {
                emptyEl.style.display = 'block';
                emptyEl.textContent = e.message || 'Lỗi tải danh sách bài viết.';
            }
        }
    }

    // 6. Render bảng bài viết
    function renderTable(posts) {
        const typeBadges = {
            event: '<span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="background-color: rgba(139, 92, 246, 0.12); color: #7c3aed; border-color: rgba(139, 92, 246, 0.28);"><i class="fa-solid fa-trophy me-1"></i>Sự kiện</span>',
            membership: '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-id-card me-1"></i>Hội viên</span>',
            announcement: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-bell me-1"></i>Thông báo</span>'
        };

        const statusBadges = {
            published: '<span class="badge bg-success-subtle text-success border border-success-subtle">Xuất bản</span>',
            draft: '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Bản nháp</span>',
            archived: '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Đã lưu trữ</span>'
        };

        tableBody.innerHTML = posts.map(p => {
            const isPinned = Number(p.is_pinned) === 1;
            const typeBadge = typeBadges[p.type] || `<span class="badge bg-secondary">${p.type}</span>`;
            let statusBadge = statusBadges[p.status] || `<span class="badge bg-secondary">${p.status}</span>`;
            if (p.status === 'published') {
                const now = new Date();
                const pubDate = p.publish_at ? new Date(p.publish_at.replace(' ', 'T')) : null;
                const expDate = p.expire_at ? new Date(p.expire_at.replace(' ', 'T')) : null;
                if (expDate && !isNaN(expDate.getTime()) && expDate <= now) {
                    statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Đã hết hạn tự ẩn (Không hiển thị trên trang công khai)"><i class="fa-solid fa-hourglass-end me-1"></i>Đã hết hạn (Ẩn)</span>';
                } else if (pubDate && !isNaN(pubDate.getTime()) && pubDate > now) {
                    statusBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle" title="Chưa tới thời điểm xuất bản"><i class="fa-regular fa-clock me-1"></i>Hẹn giờ đăng</span>';
                }
            }

            const imgHtml = p.cover_image
                ? `<img src="${BookingAccount.escape(p.cover_image)}" class="post-thumb-img" alt="Thumbnail">`
                : `<div class="thumb-placeholder"><i class="fa-solid fa-newspaper"></i></div>`;

            const previewUrl = `post-detail.php?slug=${encodeURIComponent(p.slug)}`;

            return `
                <tr id="post-row-${p.id}">
                    <td>${imgHtml}</td>
                    <td>
                        <div class="fw-bold text-truncate" style="max-width: 280px;" title="${BookingAccount.escape(p.title)}">
                            ${BookingAccount.escape(p.title)}
                        </div>
                        ${p.voucher_code ? `
                            <div class="mt-1">
                                <span class="badge bg-teal-subtle text-teal border border-teal-subtle" style="font-size:0.7rem;">
                                    <i class="fa-solid fa-ticket me-1"></i>Tặng mã: <strong>${BookingAccount.escape(p.voucher_code)}</strong> (-${p.voucher_discount_percent}%)
                                </span>
                            </div>
                        ` : ''}
                        <div class="small text-muted text-truncate font-monospace mt-1" style="max-width: 280px; font-size: 0.75rem;">
                            <a href="${previewUrl}" target="_blank" class="text-decoration-none text-muted" title="Xem thử bài viết">${BookingAccount.escape(p.slug)} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.65rem;"></i></a>
                        </div>
                    </td>
                    <td>${typeBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-link p-0 text-decoration-none btn-toggle-pin" data-id="${p.id}" data-pinned="${isPinned ? 1 : 0}" title="${isPinned ? 'Bỏ ghim' : 'Ghim bài'}">
                            <i class="fa-solid fa-thumbtack ${isPinned ? 'text-warning' : 'text-muted opacity-50'}"></i>
                        </button>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm p-0 border-0 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ${statusBadge}
                            </button>
                            <ul class="dropdown-menu shadow-sm border-0 rounded-3">
                                <li><a class="dropdown-item small btn-change-status" href="#" data-id="${p.id}" data-status="published"><i class="fa-solid fa-check text-success me-1"></i> Xuất bản (Published)</a></li>
                                <li><a class="dropdown-item small btn-change-status" href="#" data-id="${p.id}" data-status="draft"><i class="fa-solid fa-file-pen text-secondary me-1"></i> Bản nháp (Draft)</a></li>
                                <li><a class="dropdown-item small btn-change-status" href="#" data-id="${p.id}" data-status="archived"><i class="fa-solid fa-box-archive text-secondary me-1"></i> Lưu trữ (Archived)</a></li>
                            </ul>
                        </div>
                    </td>
                    <td>
                        <div class="small text-nowrap"><i class="fa-regular fa-clock me-1 text-muted"></i>${formatDateTime(p.publish_at || p.created_at)}</div>
                        ${p.expire_at ? `<div class="small text-muted text-nowrap"><i class="fa-solid fa-hourglass-end me-1 text-warning"></i>Hết hạn: ${formatDateTime(p.expire_at)}</div>` : ''}
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-teal rounded-circle me-1 btn-edit-post" data-id="${p.id}" title="Chỉnh sửa">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger rounded-circle btn-delete-post" data-id="${p.id}" data-title="${BookingAccount.escape(p.title)}" title="Xóa bài">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Gắn sự kiện các nút hành động trong bảng
        tableBody.querySelectorAll('.btn-edit-post').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(parseInt(btn.dataset.id, 10)));
        });

        tableBody.querySelectorAll('.btn-delete-post').forEach(btn => {
            btn.addEventListener('click', () => deletePost(parseInt(btn.dataset.id, 10), btn.dataset.title));
        });

        tableBody.querySelectorAll('.btn-toggle-pin').forEach(btn => {
            btn.addEventListener('click', () => togglePin(parseInt(btn.dataset.id, 10), btn.dataset.pinned === '1'));
        });

        tableBody.querySelectorAll('.btn-change-status').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                changeStatus(parseInt(btn.dataset.id, 10), btn.dataset.status);
            });
        });
    }

    // 7. Render phân trang
    function renderPagination(totalPages, totalCount) {
        if (countText) countText.textContent = `Tổng cộng: ${totalCount} bài viết`;
        if (totalPages <= 1) {
            if (paginationWrap) paginationWrap.style.display = 'none';
            return;
        }

        let html = '';
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><button class="page-link" data-p="${currentPage - 1}">&laquo;</button></li>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><button class="page-link" data-p="${i}">${i}</button></li>`;
        }
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><button class="page-link" data-p="${currentPage + 1}">&raquo;</button></li>`;

        paginationEl.innerHTML = html;
        paginationWrap.style.display = 'flex';

        paginationEl.querySelectorAll('.page-link').forEach(btn => {
            btn.addEventListener('click', () => {
                const p = parseInt(btn.dataset.p, 10);
                if (p >= 1 && p <= totalPages && p !== currentPage) {
                    currentPage = p;
                    loadPosts();
                }
            });
        });
    }

    // 8. Mở Modal Thêm mới
    btnAdd?.addEventListener('click', () => {
        form.reset();
        document.getElementById('formPostId').value = '';
        document.getElementById('postModalTitle').textContent = 'Viết bài mới';
        const vSelect = document.getElementById('formPostVoucher');
        if (vSelect) vSelect.value = '';
        selectType.value = 'event';
        updateTypeFields();
        updatePreview('');
        modal.show();
    });

    // 9. Mở Modal Chỉnh sửa
    async function openEditModal(id) {
        try {
            const post = await BookingAccount.request(`api/admin/posts.php?id=${id}`);
            if (!post) throw new Error('Không thể tải bài viết.');

            form.reset();
            document.getElementById('formPostId').value = post.id;
            document.getElementById('postModalTitle').textContent = `Chỉnh sửa: ${post.title}`;

            selectType.value = post.type;
            const vSelect = document.getElementById('formPostVoucher');
            if (vSelect) vSelect.value = post.voucher_id || '';
            document.getElementById('formPostStatus').value = post.status;
            document.getElementById('formPostPinned').checked = Number(post.is_pinned) === 1;
            document.getElementById('formPostTitle').value = post.title || '';
            document.getElementById('formPostSummary').value = post.summary || '';
            document.getElementById('formPostContent').value = post.content || '';
            coverUrlInput.value = post.cover_image || '';

            // Trường sự kiện
            document.getElementById('formEventStart').value = toInputDateTime(post.event_start);
            document.getElementById('formEventEnd').value = toInputDateTime(post.event_end);
            document.getElementById('formEventLocation').value = post.event_location || '';

            // Trường gói hội viên
            document.getElementById('formMemPrice').value = post.price !== null ? post.price : '';
            document.getElementById('formMemDuration').value = post.duration_days || '';
            const discInput = document.getElementById('formMemDiscountPercent');
            if (discInput) discInput.value = post.discount_percent || '';
            document.getElementById('formMemBenefits').value = post.benefits || '';

            // Thời gian publish/expire
            document.getElementById('formPublishAt').value = toInputDateTime(post.publish_at);
            document.getElementById('formExpireAt').value = toInputDateTime(post.expire_at);

            updateTypeFields();
            updatePreview(post.cover_image || '');

            modal.show();
        } catch (err) {
            AdminApp.toast('error', err.message);
        }
    }

    // 10. Xử lý Submit Form (Tạo mới hoặc Cập nhật)
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('formPostId').value;
        const type = selectType.value;
        const title = document.getElementById('formPostTitle').value.trim();
        const content = document.getElementById('formPostContent').value.trim();

        if (!title || !content) {
            AdminApp.toast('error', 'Vui lòng nhập đầy đủ tiêu đề và nội dung chi tiết.');
            return;
        }

        const discVal = document.getElementById('formMemDiscountPercent')?.value;
        const voucherSelectVal = document.getElementById('formPostVoucher')?.value;
        const payload = {
            type,
            voucher_id: voucherSelectVal ? parseInt(voucherSelectVal, 10) : null,
            title,
            summary: document.getElementById('formPostSummary').value.trim(),
            content,
            cover_image: coverUrlInput.value.trim(),
            status: document.getElementById('formPostStatus').value,
            is_pinned: document.getElementById('formPostPinned').checked ? 1 : 0,
            publish_at: document.getElementById('formPublishAt').value ? document.getElementById('formPublishAt').value.replace('T', ' ') + ':00' : null,
            expire_at: document.getElementById('formExpireAt').value ? document.getElementById('formExpireAt').value.replace('T', ' ') + ':00' : null,
            event_start: document.getElementById('formEventStart').value ? document.getElementById('formEventStart').value.replace('T', ' ') + ':00' : null,
            event_end: document.getElementById('formEventEnd').value ? document.getElementById('formEventEnd').value.replace('T', ' ') + ':00' : null,
            event_location: document.getElementById('formEventLocation').value.trim(),
            price: document.getElementById('formMemPrice').value !== '' ? parseFloat(document.getElementById('formMemPrice').value) : null,
            duration_days: document.getElementById('formMemDuration').value !== '' ? parseInt(document.getElementById('formMemDuration').value, 10) : null,
            discount_percent: (discVal !== undefined && discVal !== '') ? parseInt(discVal, 10) : 0,
            benefits: document.getElementById('formMemBenefits').value.trim()
        };

        if (id) {
            payload.id = parseInt(id, 10);
        }

        if (payload.publish_at && payload.expire_at) {
            const pubDate = new Date(payload.publish_at.replace(' ', 'T'));
            const expDate = new Date(payload.expire_at.replace(' ', 'T'));
            if (!isNaN(pubDate.getTime()) && !isNaN(expDate.getTime()) && pubDate >= expDate) {
                AdminApp.toast('error', 'Thời điểm hết hạn tự ẩn phải sau thời điểm xuất bản.');
                return;
            }
        }

        if (payload.status === 'published' && payload.expire_at) {
            const expDate = new Date(payload.expire_at.replace(' ', 'T'));
            if (!isNaN(expDate.getTime()) && expDate <= new Date()) {
                AdminApp.toast('error', 'Thời điểm hết hạn tự ẩn đã ở trong quá khứ nên bài viết sẽ bị ẩn ngay. Vui lòng chọn thời điểm ở tương lai hoặc để trống ô này.');
                return;
            }
        }

        saveBtn.disabled = true;
        saveSpinner.style.display = 'inline-block';

        try {
            const method = id ? 'PUT' : 'POST';
            await BookingAccount.request('api/admin/posts.php', {
                method,
                body: JSON.stringify(payload)
            });

            AdminApp.toast('success', id ? 'Cập nhật bài viết thành công!' : 'Tạo bài viết mới thành công!');
            modal.hide();
            loadPosts();
        } catch (err) {
            AdminApp.toast('error', err.message);
        } finally {
            saveBtn.disabled = false;
            saveSpinner.style.display = 'none';
        }
    });

    // 11. Đổi trạng thái nhanh
    async function changeStatus(id, newStatus) {
        try {
            await BookingAccount.request('api/admin/posts.php', {
                method: 'PATCH',
                body: JSON.stringify({ id, status: newStatus })
            });
            AdminApp.toast('success', 'Đã đổi trạng thái thành công.');
            loadPosts();
        } catch (err) {
            AdminApp.toast('error', err.message);
        }
    }

    // 12. Ghim / Bỏ ghim nhanh
    async function togglePin(id, currentPinned) {
        try {
            await BookingAccount.request('api/admin/posts.php', {
                method: 'PATCH',
                body: JSON.stringify({ id, is_pinned: !currentPinned })
            });
            AdminApp.toast('success', currentPinned ? 'Đã bỏ ghim bài viết.' : 'Đã ghim bài viết lên đầu.');
            loadPosts();
        } catch (err) {
            AdminApp.toast('error', err.message);
        }
    }

    // 13. Xóa bài viết
    async function deletePost(id, title) {
        const confirmed = await AdminApp.confirmAction(
            'Xác nhận xóa bài viết?',
            `Bạn có chắc chắn muốn xóa vĩnh viễn bài viết "${title}" không? Thao tác này không thể hoàn tác.`
        );
        if (!confirmed) return;

        try {
            await BookingAccount.request('api/admin/posts.php', {
                method: 'DELETE',
                body: JSON.stringify({ id })
            });
            AdminApp.toast('success', 'Đã xóa bài viết thành công.');
            loadPosts();
        } catch (err) {
            AdminApp.toast('error', err.message);
        }
    }

    // 14. Bộ lọc & Tìm kiếm
    filterType?.addEventListener('change', () => { currentPage = 1; loadPosts(); });
    filterStatus?.addEventListener('change', () => { currentPage = 1; loadPosts(); });
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            currentPage = 1;
            loadPosts();
        }, 350);
    });

    // Tải dữ liệu ban đầu
    loadPosts();
});
