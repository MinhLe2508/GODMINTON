<?php
// Run with PHP CLI to add the two requested editorial articles, without duplicates.
if (PHP_SAPI !== 'cli') {http_response_code(403);exit('CLI only');}
require_once __DIR__ . '/../config/db.php';
$articles = [
    [
        'title'=>'Giảm 20% buổi đầu tiên cho khách mới',
        'slug'=>'giam-20-phan-tram-buoi-dau-tien-cho-khach-moi',
        'summary'=>'Ưu đãi giảm 20% buổi đầu tiên dành cho khách mới. Liên hệ để biết điều kiện và cách nhận ưu đãi.',
        'content'=>"Giảm 20% buổi đầu tiên cho khách mới.\n\nThông tin về sân áp dụng, thời gian triển khai, điều kiện và cách nhận ưu đãi đang được cập nhật. Vui lòng liên hệ bộ phận hỗ trợ để xác nhận trước khi đặt sân.",
        'category'=>'promo'
    ],
    [
        'title'=>'Khóa học cầu lông cơ bản – Trình mới bắt đầu của HLV Nguyễn Tiến Minh',
        'slug'=>'khoa-hoc-cau-long-co-ban-hlv-nguyen-tien-minh',
        'summary'=>'Khóa học cầu lông cơ bản dành cho người mới bắt đầu của HLV Nguyễn Tiến Minh. Lịch học, địa điểm và học phí đang được cập nhật.',
        'content'=>"Khóa học cầu lông cơ bản – Trình mới bắt đầu của HLV Nguyễn Tiến Minh.\n\nLịch học, địa điểm, học phí, nội dung chương trình và hình thức đăng ký đang được cập nhật. Vui lòng liên hệ bộ phận hỗ trợ để tìm hiểu thêm.",
        'category'=>'class'
    ]
];
$columns = array_column($pdo->query('SHOW COLUMNS FROM news')->fetchAll(), 'Field');
$hasSlug = in_array('slug',$columns,true);
$pdo->beginTransaction();
try {
    foreach ($articles as $article) {
        $find = $pdo->prepare($hasSlug ? 'SELECT id FROM news WHERE slug=? OR title=? LIMIT 1' : 'SELECT id FROM news WHERE title=? LIMIT 1');
        $find->execute($hasSlug ? [$article['slug'],$article['title']] : [$article['title']]);
        if ($find->fetchColumn()) {echo "Already exists: ",$article['slug'],"\n";continue;}
        $names = ['title','summary','content','category','status','published_at'];
        $values = [$article['title'],$article['summary'],$article['content'],$article['category'],'published',date('Y-m-d H:i:s')];
        if ($hasSlug) {$names[]='slug';$values[]=$article['slug'];}
        $stmt=$pdo->prepare('INSERT INTO news('.implode(',',$names).') VALUES('.implode(',',array_fill(0,count($names),'?')).')');
        $stmt->execute($values);
        echo 'Added article #',$pdo->lastInsertId(),' (',$article['category'],")\n";
    }
    $pdo->commit();
} catch (Throwable $error) {$pdo->rollBack();throw $error;}
