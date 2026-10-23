<?php
require_once 'c:/xampp/htdocs/GODMINTON/config/db.php';

echo "=== CẬP NHẬT 3 GÓI HỘI VIÊN THEO THÁNG (199K, 349K, 499K) ===\n";

// 1. Gói Bạc (Post ID 4)
$p4 = $pdo->prepare("
    UPDATE posts 
    SET price = 199000, 
        duration_days = 30,
        summary = 'Gói hội viên 1 tháng linh hoạt dành cho người chơi thường xuyên, tự động giảm ngay 5% cho mọi lượt đặt sân trên toàn hệ thống GODMINTON.',
        content = '### Thông tin chi tiết gói Bạc\nGói **Silver Feather** là sự lựa chọn hoàn hảo cho những ai muốn duy trì thói quen tập luyện thể thao 2-3 buổi mỗi tuần mà vẫn tối ưu được ngân sách đặt sân.\n\n### Đặc quyền khi kích hoạt\n- Tự động trừ 5% trực tiếp trên tổng hóa đơn đặt sân.\n- Được áp dụng cộng dồn hoặc ưu tiên cùng mã giảm giá nếu hợp lệ.\n- Thời hạn sử dụng: 1 tháng (30 ngày) kể từ ngày kích hoạt.'
    WHERE id = 4
");
$p4->execute();

// 2. Gói Vàng (Post ID 5)
$p5 = $pdo->prepare("
    UPDATE posts 
    SET price = 349000, 
        duration_days = 30,
        summary = 'Gói hội viên 1 tháng tối ưu chi phí cho các câu lạc bộ và nhóm cố định hàng tuần với mức giảm giá 10% toàn sàn.',
        content = '### Đặc quyền dành cho câu lạc bộ\nĐược thiết kế cho các nhóm chơi thường xuyên, gói **Golden Racket** giúp thành viên an tâm về chỗ đánh mỗi tuần mà không lo cảnh giành sân giờ cao điểm.\n\n### Chính sách hỗ trợ\n- Giữ khung giờ vàng trước 7 ngày không lo hết chỗ.\n- Miễn phí hủy đơn trước 6 tiếng không bị mất tiền cọc.\n- Tặng kèm 01 ống cầu thi đấu chính hãng khi kích hoạt.\n- Thời hạn sử dụng: 1 tháng (30 ngày) kể từ ngày kích hoạt.'
    WHERE id = 5
");
$p5->execute();

// 3. Gói Bạch Kim (Post ID 6)
$p6 = $pdo->prepare("
    UPDATE posts 
    SET price = 499000, 
        duration_days = 30,
        summary = 'Gói hội viên 1 tháng cao cấp nhất dành cho vận động viên, hội nhóm và doanh nghiệp với mức chiết khấu tối đa 15% toàn hệ thống.',
        content = '### Trải nghiệm VIP không giới hạn\nGói hội viên cao cấp nhất mang lại sự thoải mái tuyệt đối cho các tổ chức, doanh nghiệp hoặc người chơi chuyên nghiệp với đặc quyền tối đa trên mạng lưới đối tác sân cầu lông GODMINTON.\n\n### Đặc quyền VIP\n- Giảm 15% trực tiếp trên tổng hóa đơn đặt sân toàn sàn.\n- Ưu tiên giữ lịch cố định tháng và phục vụ VIP.\n- Miễn phí hủy đơn trước 4 tiếng.\n- Thời hạn sử dụng: 1 tháng (30 ngày) kể từ ngày kích hoạt.'
    WHERE id = 6
");
$p6->execute();

echo "Cập nhật Database thành công!\n";

$stmt = $pdo->query("SELECT id, title, price, duration_days, discount_percent FROM posts WHERE type = 'membership' ORDER BY id ASC");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "ID {$r['id']}: {$r['title']} | Giá: " . number_format($r['price']) . "đ | Hạn: {$r['duration_days']} ngày | Giảm: {$r['discount_percent']}%\n";
}
