<?php
/**
 * includes/post-service.php
 * Nghiệp vụ xử lý Bài viết (Khám phá: Sự kiện, Gói hội viên ưu đãi, Thông báo).
 * Dùng chung cho cả API công khai phía khách và Kênh Quản trị (Admin).
 */

require_once __DIR__ . '/../config/db.php';

class PostError extends RuntimeException {
    public function __construct(
        public int $httpCode,
        public string $errorCode,
        string $message,
        public array $meta = []
    ) {
        parent::__construct($message);
    }
}

/**
 * Loại bỏ dấu tiếng Việt chuẩn
 */
function post_remove_vietnamese_accents(string $str): string {
    $map = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'd' => 'đ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        'D' => 'Đ',
    ];
    foreach ($map as $nonAccent => $accentRegex) {
        $str = preg_replace("/($accentRegex)/u", $nonAccent, $str);
    }
    return $str;
}

/**
 * Sinh chuỗi slug từ tiêu đề
 */
function post_slugify(string $text): string {
    $text = post_remove_vietnamese_accents($text);
    $text = mb_strtolower($text, 'UTF-8');
    // Thay thế ký tự không phải chữ và số thành dấu gạch nối
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'bai-viet-' . bin2hex(random_bytes(3));
}

/**
 * Sinh slug duy nhất trong CSDL (tự thêm -2, -3 nếu trùng)
 */
function post_generate_unique_slug(PDO $pdo, string $title, ?int $excludeId = null): string {
    $baseSlug = post_slugify($title);
    $slug = $baseSlug;
    $counter = 1;

    while (true) {
        $sql = 'SELECT id FROM posts WHERE slug = ?';
        $params = [$slug];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $counter++;
        $slug = $baseSlug . '-' . $counter;
    }
}

/**
 * Hiển thị nội dung an toàn chống XSS, hỗ trợ định dạng rút gọn (Markdown cơ bản)
 */
function post_render_content(string $content): string {
    // 1. Escape toàn bộ HTML thô để chống tuyệt đối tấn công XSS (<script>, onerror, iframe, ...)
    $safe = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    // 2. Chuyển đổi định dạng tiêu đề Markdown: ### Tiêu đề -> <h5>
    $safe = preg_replace('/^###\s+(.+)$/m', '<h5 class="fw-bold mt-3 mb-2 text-teal">$1</h5>', $safe);
    $safe = preg_replace('/^##\s+(.+)$/m', '<h4 class="fw-bold mt-3 mb-2">$1</h4>', $safe);

    // 3. Chuyển đổi in đậm **text** -> <strong>text</strong>
    $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);

    // 4. Chuyển đổi in nghiêng *text* -> <em>text</em>
    $safe = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $safe);

    // 5. Chuyển đổi gạch đầu dòng danh sách: - mục -> bullet item
    $safe = preg_replace('/^[-*]\s+(.+)$/m', '<div class="d-flex align-items-start gap-2 my-1"><i class="fa-solid fa-circle-check text-teal mt-1 small"></i><span>$1</span></div>', $safe);

    // 6. Chuyển đổi link Markdown an toàn [Title](http(s)://URL)
    $safe = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/i', function($matches) {
        return '<a href="' . $matches[2] . '" target="_blank" rel="noopener noreferrer" class="text-teal text-decoration-underline fw-semibold">' . $matches[1] . '</a>';
    }, $safe);

    // 7. Chuyển đổi xuống dòng
    return nl2br($safe);
}

/**
 * Định dạng tiền tệ VNĐ
 */
function post_format_vnd(int|float|null $amount): string {
    if ($amount === null || $amount < 0) return '0đ';
    return number_format((float)$amount, 0, ',', '.') . 'đ';
}

/**
 * Lấy danh sách bài viết công khai cho khách hàng theo quy tắc Mục 2.4:
 * status = 'published' AND (publish_at IS NULL OR publish_at <= NOW()) AND (expire_at IS NULL OR expire_at > NOW())
 */
function post_get_public_list(PDO $pdo, array $filters = []): array {
    $type = isset($filters['type']) ? trim((string)$filters['type']) : '';
    $page = max(1, (int)($filters['page'] ?? 1));
    $limit = min(50, max(1, (int)($filters['limit'] ?? 9)));
    $offset = ($page - 1) * $limit;
    $search = isset($filters['search']) ? trim((string)$filters['search']) : '';

    $where = [
        "p.status = 'published'",
        "(p.publish_at IS NULL OR p.publish_at <= NOW())",
        "(p.expire_at IS NULL OR p.expire_at > NOW())"
    ];
    $params = [];

    if ($type !== '' && in_array($type, ['event', 'membership', 'announcement'], true)) {
        $where[] = 'p.type = ?';
        $params[] = $type;
    }

    if ($search !== '') {
        $where[] = '(p.title LIKE ? OR p.summary LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSql = implode(' AND ', $where);

    // Đếm tổng số bản ghi
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Lấy danh sách bài viết: Ghim trước, sau đó sắp xếp theo publish_at / created_at mới nhất
    $querySql = "
        SELECT p.id, p.type, p.title, p.slug, p.summary, p.cover_image,
               p.publish_at, p.expire_at, p.is_pinned, p.created_at,
               p.event_start, p.event_end, p.event_location,
               p.price, p.duration_days, p.benefits,
               u.name AS author_name
        FROM posts p
        LEFT JOIN users u ON u.id = p.created_by
        WHERE {$whereSql}
        ORDER BY p.is_pinned DESC, COALESCE(p.publish_at, p.created_at) DESC, p.id DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($querySql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => (int)ceil($total / $limit) ?: 1
    ];
}

/**
 * Lấy chi tiết bài viết công khai theo slug (Quy tắc hiển thị Mục 2.4).
 * Nếu không đủ điều kiện (bài nháp, bài đã ẩn, chưa tới giờ đăng, quá hạn) -> trả về null để caller phản hồi 404.
 */
function post_get_public_by_slug(PDO $pdo, string $slug): ?array {
    $slug = trim($slug);
    if ($slug === '') return null;

    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS author_name
        FROM posts p
        LEFT JOIN users u ON u.id = p.created_by
        WHERE p.slug = ?
          AND p.status = 'published'
          AND (p.publish_at IS NULL OR p.publish_at <= NOW())
          AND (p.expire_at IS NULL OR p.expire_at > NOW())
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    return $post ?: null;
}

/**
 * Lấy danh sách bài viết dành cho Admin (không lọc quy tắc publish, hiển thị cả draft/archived)
 */
function post_get_admin_list(PDO $pdo, array $filters = []): array {
    $type = isset($filters['type']) ? trim((string)$filters['type']) : '';
    $status = isset($filters['status']) ? trim((string)$filters['status']) : '';
    $search = isset($filters['search']) ? trim((string)$filters['search']) : '';
    $page = max(1, (int)($filters['page'] ?? 1));
    $limit = min(50, max(1, (int)($filters['limit'] ?? 15)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($type !== '' && in_array($type, ['event', 'membership', 'announcement'], true)) {
        $where[] = 'p.type = ?';
        $params[] = $type;
    }

    if ($status !== '' && in_array($status, ['draft', 'published', 'archived'], true)) {
        $where[] = 'p.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = '(p.title LIKE ? OR p.slug LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $querySql = "
        SELECT p.*, u.name AS author_name
        FROM posts p
        LEFT JOIN users u ON u.id = p.created_by
        {$whereSql}
        ORDER BY p.is_pinned DESC, p.id DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($querySql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => (int)ceil($total / $limit) ?: 1
    ];
}

/**
 * Lấy chi tiết bài viết theo ID (dành cho Admin)
 */
function post_get_by_id(PDO $pdo, int $id): ?array {
    if ($id <= 0) return null;
    $stmt = $pdo->prepare('SELECT p.*, u.name AS author_name FROM posts p LEFT JOIN users u ON u.id = p.created_by WHERE p.id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    return $post ?: null;
}

/**
 * Validate dữ liệu bài viết
 */
function post_validate_payload(array $data, bool $isUpdate = false): array {
    $title = trim((string)($data['title'] ?? ''));
    if ($title === '') {
        throw new PostError(422, 'EMPTY_TITLE', 'Tiêu đề bài viết không được để trống.');
    }
    if (mb_strlen($title, 'UTF-8') < 3 || mb_strlen($title, 'UTF-8') > 255) {
        throw new PostError(422, 'INVALID_TITLE_LENGTH', 'Tiêu đề bài viết phải từ 3 đến 255 ký tự.');
    }

    $type = trim((string)($data['type'] ?? ''));
    if (!in_array($type, ['event', 'membership', 'announcement'], true)) {
        throw new PostError(422, 'INVALID_TYPE', 'Loại bài viết phải là Sự kiện (event), Gói hội viên (membership) hoặc Thông báo (announcement).');
    }

    $content = trim((string)($data['content'] ?? ''));
    if ($content === '') {
        throw new PostError(422, 'EMPTY_CONTENT', 'Nội dung chi tiết bài viết không được để trống.');
    }

    $summary = trim((string)($data['summary'] ?? ''));
    $coverImage = trim((string)($data['cover_image'] ?? ''));
    if ($coverImage !== '' && strlen($coverImage) > 2048) {
        throw new PostError(422, 'IMAGE_URL_TOO_LONG', 'Đường dẫn ảnh bìa quá dài.');
    }

    $status = trim((string)($data['status'] ?? 'draft'));
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $status = 'draft';
    }

    $publishAt = !empty($data['publish_at']) ? trim((string)$data['publish_at']) : null;
    $expireAt = !empty($data['expire_at']) ? trim((string)$data['expire_at']) : null;

    if ($publishAt && $expireAt && strtotime($publishAt) >= strtotime($expireAt)) {
        throw new PostError(422, 'INVALID_PUBLISH_RANGE', 'Thời gian hết hạn phải sau thời gian xuất bản.');
    }

    $isPinned = !empty($data['is_pinned']) ? 1 : 0;

    // Validate trường đặc thù Sự kiện
    $eventStart = null;
    $eventEnd = null;
    $eventLocation = null;
    if ($type === 'event') {
        $eventStart = !empty($data['event_start']) ? trim((string)$data['event_start']) : null;
        $eventEnd = !empty($data['event_end']) ? trim((string)$data['event_end']) : null;
        $eventLocation = !empty($data['event_location']) ? trim((string)$data['event_location']) : null;

        if ($eventStart && $eventEnd && strtotime($eventStart) > strtotime($eventEnd)) {
            throw new PostError(422, 'INVALID_EVENT_RANGE', 'Thời gian kết thúc sự kiện phải bằng hoặc sau thời gian bắt đầu.');
        }
    }

    // Validate trường đặc thù Gói hội viên
    $price = null;
    $durationDays = null;
    $benefits = null;
    if ($type === 'membership') {
        if (isset($data['price']) && $data['price'] !== '') {
            $price = (float)$data['price'];
            if ($price < 0) {
                throw new PostError(422, 'INVALID_PRICE', 'Giá gói hội viên không được âm.');
            }
        }
        if (isset($data['duration_days']) && $data['duration_days'] !== '') {
            $durationDays = (int)$data['duration_days'];
            if ($durationDays <= 0) {
                throw new PostError(422, 'INVALID_DURATION', 'Thời hạn gói phải lớn hơn 0 ngày.');
            }
        }
        $benefits = !empty($data['benefits']) ? trim((string)$data['benefits']) : null;
    }

    return [
        'type' => $type,
        'title' => $title,
        'summary' => $summary ?: null,
        'content' => $content,
        'cover_image' => $coverImage ?: null,
        'status' => $status,
        'publish_at' => $publishAt,
        'expire_at' => $expireAt,
        'is_pinned' => $isPinned,
        'event_start' => $eventStart,
        'event_end' => $eventEnd,
        'event_location' => $eventLocation,
        'price' => $price,
        'duration_days' => $durationDays,
        'benefits' => $benefits
    ];
}

/**
 * Thêm bài viết mới (Admin)
 */
function post_create(PDO $pdo, int $adminId, array $data): int {
    $validated = post_validate_payload($data, false);
    $slug = post_generate_unique_slug($pdo, $validated['title']);

    $stmt = $pdo->prepare('
        INSERT INTO posts (
            type, title, slug, summary, content, cover_image, status,
            publish_at, expire_at, is_pinned, created_by,
            event_start, event_end, event_location,
            price, duration_days, benefits, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, NOW(), NOW()
        )
    ');

    $stmt->execute([
        $validated['type'],
        $validated['title'],
        $slug,
        $validated['summary'],
        $validated['content'],
        $validated['cover_image'],
        $validated['status'],
        $validated['publish_at'],
        $validated['expire_at'],
        $validated['is_pinned'],
        $adminId,
        $validated['event_start'],
        $validated['event_end'],
        $validated['event_location'],
        $validated['price'],
        $validated['duration_days'],
        $validated['benefits']
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Cập nhật bài viết (Admin)
 */
function post_update(PDO $pdo, int $id, array $data): bool {
    $existing = post_get_by_id($pdo, $id);
    if (!$existing) {
        throw new PostError(404, 'POST_NOT_FOUND', 'Bài viết không tồn tại.');
    }

    $validated = post_validate_payload($data, true);

    // Cập nhật slug nếu tiêu đề thay đổi hoặc nếu có yêu cầu
    $slug = $existing['slug'];
    if ($validated['title'] !== $existing['title']) {
        $slug = post_generate_unique_slug($pdo, $validated['title'], $id);
    }

    $stmt = $pdo->prepare('
        UPDATE posts SET
            type = ?,
            title = ?,
            slug = ?,
            summary = ?,
            content = ?,
            cover_image = ?,
            status = ?,
            publish_at = ?,
            expire_at = ?,
            is_pinned = ?,
            event_start = ?,
            event_end = ?,
            event_location = ?,
            price = ?,
            duration_days = ?,
            benefits = ?,
            updated_at = NOW()
        WHERE id = ?
    ');

    return $stmt->execute([
        $validated['type'],
        $validated['title'],
        $slug,
        $validated['summary'],
        $validated['content'],
        $validated['cover_image'],
        $validated['status'],
        $validated['publish_at'],
        $validated['expire_at'],
        $validated['is_pinned'],
        $validated['event_start'],
        $validated['event_end'],
        $validated['event_location'],
        $validated['price'],
        $validated['duration_days'],
        $validated['benefits'],
        $id
    ]);
}

/**
 * Thay đổi trạng thái bài viết (draft, published, archived)
 */
function post_toggle_status(PDO $pdo, int $id, string $status): bool {
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        throw new PostError(422, 'INVALID_STATUS', 'Trạng thái không hợp lệ.');
    }
    $stmt = $pdo->prepare('UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

/**
 * Ghim / Bỏ ghim bài viết
 */
function post_toggle_pin(PDO $pdo, int $id, bool $isPinned): bool {
    $stmt = $pdo->prepare('UPDATE posts SET is_pinned = ?, updated_at = NOW() WHERE id = ?');
    return $stmt->execute([$isPinned ? 1 : 0, $id]);
}

/**
 * Xóa bài viết
 */
function post_delete(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
    return $stmt->execute([$id]);
}
