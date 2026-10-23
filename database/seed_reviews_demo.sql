-- database/seed_reviews_demo.sql
-- Dữ liệu MẪU cho "Đánh Giá Từ Vợt Thủ": 125 đánh giá của 118 tài khoản demo trên 41 sân.
-- Yêu cầu: đã import database/migration_reviews.sql. Chọn database badminton_booking trước khi import.
-- Chạy lại nhiều lần an toàn (không tạo trùng). Không động tới tài khoản/đánh giá thật.
-- Tài khoản demo có email dạng demo.*@demo.godminton.local, trạng thái 'locked' nên KHÔNG đăng nhập được.
-- Muốn xóa toàn bộ dữ liệu demo (đánh giá tự xóa theo):
--   DELETE FROM users WHERE email LIKE 'demo.%@demo.godminton.local';

-- 1. Tài khoản demo (bị khóa, mật khẩu không hợp lệ)
INSERT IGNORE INTO `users` (`name`,`email`,`password_hash`,`role`,`is_verified`,`account_status`,`locked_reason`) VALUES
('Anh Tú G.','demo.anh.tu.g@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Anh Tú P.','demo.anh.tu.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bình Minh T.','demo.binh.minh.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng D.','demo.bich.phuong.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng R.','demo.bich.phuong.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng S.','demo.bich.phuong.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng V.','demo.bich.phuong.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bảo Trân C.','demo.bao.tran.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công E.','demo.chi.cong.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công S.','demo.chi.cong.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công T.','demo.chi.cong.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công X.','demo.chi.cong.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Công Danh H.','demo.cong.danh.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Công Danh K.','demo.cong.danh.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Diễm My H.','demo.diem.my.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh B.','demo.duy.anh.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh E.','demo.duy.anh.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh H.','demo.duy.anh.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh Q.','demo.duy.anh.q@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh S.','demo.duy.anh.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy A.','demo.gia.huy.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy B.','demo.gia.huy.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy C.','demo.gia.huy.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy N.','demo.gia.huy.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hoàng Long R.','demo.hoang.long.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hoàng Long X.','demo.hoang.long.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My D.','demo.ha.my.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My G.','demo.ha.my.g@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My H.','demo.ha.my.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My L.','demo.ha.my.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My M.','demo.ha.my.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hải Yến M.','demo.hai.yen.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hải Yến T.','demo.hai.yen.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hồng Nhung N.','demo.hong.nhung.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hồng Nhung T.','demo.hong.nhung.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hồng Nhung V.','demo.hong.nhung.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước B.','demo.huu.phuoc.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước L.','demo.huu.phuoc.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước P.','demo.huu.phuoc.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước S.','demo.huu.phuoc.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước T.','demo.huu.phuoc.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Khánh Vy A.','demo.khanh.vy.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân C.','demo.kim.ngan.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân E.','demo.kim.ngan.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân R.','demo.kim.ngan.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân S.','demo.kim.ngan.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân T.','demo.kim.ngan.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Lan Chi N.','demo.lan.chi.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mai Anh X.','demo.mai.anh.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Minh Khoa R.','demo.minh.khoa.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên E.','demo.my.duyen.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên M.','demo.my.duyen.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên S.','demo.my.duyen.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên T.','demo.my.duyen.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh A.','demo.ngoc.anh.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh Q.','demo.ngoc.anh.q@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh R.','demo.ngoc.anh.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh V.','demo.ngoc.anh.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Như Quỳnh H.','demo.nhu.quynh.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Như Quỳnh N.','demo.nhu.quynh.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Như Quỳnh R.','demo.nhu.quynh.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Nhật Nam H.','demo.nhat.nam.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Nhật Nam L.','demo.nhat.nam.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Nhật Nam N.','demo.nhat.nam.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Phương Linh A.','demo.phuong.linh.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Phương Linh B.','demo.phuong.linh.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Phương Linh C.','demo.phuong.linh.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quang Vinh C.','demo.quang.vinh.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo D.','demo.quoc.bao.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo K.','demo.quoc.bao.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo L.','demo.quoc.bao.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo M.','demo.quoc.bao.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo V.','demo.quoc.bao.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm K.','demo.thanh.tam.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm M.','demo.thanh.tam.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm T.','demo.thanh.tam.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm X.','demo.thanh.tam.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thu Hà C.','demo.thu.ha.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thu Hà L.','demo.thu.ha.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thu Hà T.','demo.thu.ha.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thùy Dương C.','demo.thuy.duong.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thùy Dương Q.','demo.thuy.duong.q@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thùy Dương V.','demo.thuy.duong.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thảo Nguyên B.','demo.thao.nguyen.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thế Vinh A.','demo.the.vinh.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thế Vinh K.','demo.the.vinh.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thế Vinh N.','demo.the.vinh.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên D.','demo.trung.kien.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên E.','demo.trung.kien.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên N.','demo.trung.kien.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên S.','demo.trung.kien.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên V.','demo.trung.kien.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trọng Nghĩa K.','demo.trong.nghia.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trọng Nghĩa N.','demo.trong.nghia.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trọng Nghĩa T.','demo.trong.nghia.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tuấn Kiệt P.','demo.tuan.kiet.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát K.','demo.tan.phat.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát M.','demo.tan.phat.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát P.','demo.tan.phat.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát S.','demo.tan.phat.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát V.','demo.tan.phat.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tố Uyên D.','demo.to.uyen.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tố Uyên M.','demo.to.uyen.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Văn Hậu A.','demo.van.hau.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Văn Hậu M.','demo.van.hau.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Văn Hậu S.','demo.van.hau.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Yến Nhi H.','demo.yen.nhi.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Yến Nhi V.','demo.yen.nhi.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ái Vy E.','demo.ai.vy.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ái Vy X.','demo.ai.vy.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đình Khôi H.','demo.dinh.khoi.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đình Khôi L.','demo.dinh.khoi.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đình Khôi P.','demo.dinh.khoi.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa G.','demo.dang.khoa.g@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa K.','demo.dang.khoa.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa P.','demo.dang.khoa.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa V.','demo.dang.khoa.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đức Thịnh E.','demo.duc.thinh.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu');

-- 2. Đánh giá mẫu. Bỏ qua sân không tồn tại trong DB và đánh giá đã có.
INSERT IGNORE INTO `reviews` (`court_id`,`user_id`,`rating`,`comment`,`created_at`,`updated_at`)
SELECT c.id,u.id,v.rating,v.comment,v.ts,v.ts FROM (
SELECT 2 AS court_id,'demo.van.hau.m@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Quên mang vợt vẫn có chỗ cho thuê, tiện khi rủ bạn mới chơi.' AS comment,'2026-08-11 21:38:00' AS ts
UNION ALL
SELECT 2 AS court_id,'demo.my.duyen.t@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Có tủ khóa nên để đồ yên tâm hơn. Phòng tắm tiện cho ai đi làm về ghé đánh.' AS comment,'2026-08-24 22:00:00' AS ts
UNION ALL
SELECT 2 AS court_id,'demo.gia.huy.a@demo.godminton.local' AS email,3 AS rating,'Trải nghiệm ở mức bình thường. Vị trí ở Bình Thạnh (cũ), đi lại thuận tiện. Giờ tối và cuối tuần khá đông, nên đặt trước để có khung giờ đẹp.' AS comment,'2026-09-11 19:38:00' AS ts
UNION ALL
SELECT 2 AS court_id,'demo.hai.yen.m@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt.' AS comment,'2026-08-24 10:45:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.chi.cong.s@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Có canteen/cafe để nghỉ giữa các set và uống nước. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-06 07:00:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.khanh.vy.a@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Phòng tắm tiện cho ai đi làm về ghé đánh.' AS comment,'2026-10-09 17:15:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.kim.ngan.s@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Vị trí ở Thuận An, Bình Dương, đi lại thuận tiện. Điều hòa giúp đánh lâu mà không bị mệt vì nóng.' AS comment,'2026-08-19 19:15:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.van.hau.s@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Có cho thuê vợt nên dẫn người mới đi cùng rất dễ. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-09-17 16:00:00' AS ts
UNION ALL
SELECT 22 AS court_id,'demo.huu.phuoc.t@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Quên mang vợt vẫn có chỗ cho thuê, tiện khi rủ bạn mới chơi.' AS comment,'2026-09-07 11:45:00' AS ts
UNION ALL
SELECT 22 AS court_id,'demo.chi.cong.e@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Phòng tắm tiện cho ai đi làm về ghé đánh. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-20 12:15:00' AS ts
UNION ALL
SELECT 22 AS court_id,'demo.bich.phuong.r@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Có canteen/cafe để nghỉ giữa các set và uống nước.' AS comment,'2026-09-11 07:45:00' AS ts
UNION ALL
SELECT 24 AS court_id,'demo.thu.ha.c@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Sân có điều hòa, đánh liền mấy set vẫn dễ chịu. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-10-06 09:45:00' AS ts
UNION ALL
SELECT 24 AS court_id,'demo.duy.anh.e@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có tủ khóa nên để đồ yên tâm hơn. Wi-Fi ổn, tiện cho lúc chờ đến lượt.' AS comment,'2026-10-14 07:07:00' AS ts
UNION ALL
SELECT 24 AS court_id,'demo.dinh.khoi.p@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Vị trí ở Quận 6 (cũ), đi lại thuận tiện.' AS comment,'2026-08-26 09:53:00' AS ts
UNION ALL
SELECT 30 AS court_id,'demo.thuy.duong.q@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Vị trí ở Quận 7 (cũ), đi lại thuận tiện. Chắc chắn sẽ quay lại.' AS comment,'2026-10-11 11:07:00' AS ts
UNION ALL
SELECT 30 AS court_id,'demo.chi.cong.t@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Phòng tắm tiện cho ai đi làm về ghé đánh. Có cho thuê vợt nên dẫn người mới đi cùng rất dễ.' AS comment,'2026-10-17 15:45:00' AS ts
UNION ALL
SELECT 30 AS court_id,'demo.tuan.kiet.p@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-08-25 15:22:00' AS ts
UNION ALL
SELECT 42 AS court_id,'demo.my.duyen.s@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giải lao ở canteen sau trận rất thoải mái.' AS comment,'2026-08-12 13:07:00' AS ts
UNION ALL
SELECT 42 AS court_id,'demo.the.vinh.n@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Phòng tắm tiện cho ai đi làm về ghé đánh.' AS comment,'2026-10-11 13:38:00' AS ts
UNION ALL
SELECT 42 AS court_id,'demo.ngoc.anh.q@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-09-10 18:38:00' AS ts
UNION ALL
SELECT 65 AS court_id,'demo.bao.tran.c@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-11 15:00:00' AS ts
UNION ALL
SELECT 65 AS court_id,'demo.bich.phuong.v@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết. Giá hơi cao một chút, bù lại sân đánh thoải mái. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-13 08:38:00' AS ts
UNION ALL
SELECT 65 AS court_id,'demo.anh.tu.g@demo.godminton.local' AS email,3 AS rating,'Đánh được, nhưng có vài điểm mình thấy cần lưu ý. Vị trí ở Quận 1 (cũ), đi lại thuận tiện. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-08-31 18:22:00' AS ts
UNION ALL
SELECT 68 AS court_id,'demo.hong.nhung.t@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-08-31 14:45:00' AS ts
UNION ALL
SELECT 68 AS court_id,'demo.thanh.tam.x@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-08-28 15:38:00' AS ts
UNION ALL
SELECT 68 AS court_id,'demo.thuy.duong.v@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Vị trí ở Quận 2 (cũ), đi lại thuận tiện. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện. Chắc chắn sẽ quay lại.' AS comment,'2026-10-04 15:15:00' AS ts
UNION ALL
SELECT 69 AS court_id,'demo.my.duyen.e@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Nằm ở khu vực Quận 2 (cũ), dễ tìm. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Chắc chắn sẽ quay lại.' AS comment,'2026-08-13 20:07:00' AS ts
UNION ALL
SELECT 69 AS court_id,'demo.to.uyen.m@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-03 12:53:00' AS ts
UNION ALL
SELECT 69 AS court_id,'demo.tan.phat.s@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-08-06 19:53:00' AS ts
UNION ALL
SELECT 70 AS court_id,'demo.phuong.linh.c@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Nằm ở khu vực Quận 2 (cũ), dễ tìm. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-29 22:45:00' AS ts
UNION ALL
SELECT 70 AS court_id,'demo.duy.anh.s@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Điều hòa giúp đánh lâu mà không bị mệt vì nóng. Chắc chắn sẽ quay lại.' AS comment,'2026-10-03 20:15:00' AS ts
UNION ALL
SELECT 70 AS court_id,'demo.minh.khoa.r@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-10-07 18:45:00' AS ts
UNION ALL
SELECT 74 AS court_id,'demo.dang.khoa.g@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Có điều hòa nên đánh buổi tối không bị ngột ngạt. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-17 10:53:00' AS ts
UNION ALL
SELECT 74 AS court_id,'demo.dinh.khoi.l@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Vị trí ở Quận 4 (cũ), đi lại thuận tiện.' AS comment,'2026-10-04 17:38:00' AS ts
UNION ALL
SELECT 74 AS court_id,'demo.nhat.nam.h@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện.' AS comment,'2026-10-17 12:15:00' AS ts
UNION ALL
SELECT 75 AS court_id,'demo.quoc.bao.k@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-10-13 17:15:00' AS ts
UNION ALL
SELECT 75 AS court_id,'demo.thanh.tam.m@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-09-20 08:45:00' AS ts
UNION ALL
SELECT 75 AS court_id,'demo.tan.phat.p@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-08-06 08:22:00' AS ts
UNION ALL
SELECT 76 AS court_id,'demo.my.duyen.m@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Chắc chắn sẽ quay lại.' AS comment,'2026-09-02 07:07:00' AS ts
UNION ALL
SELECT 76 AS court_id,'demo.quoc.bao.v@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Vị trí ở Quận 4 (cũ), đi lại thuận tiện. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-08-07 17:07:00' AS ts
UNION ALL
SELECT 76 AS court_id,'demo.hong.nhung.v@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá hợp lý cho chất lượng nhận được. Đánh xong có chỗ tắm nên đỡ phải về nhà mới thay đồ.' AS comment,'2026-10-09 19:15:00' AS ts
UNION ALL
SELECT 83 AS court_id,'demo.cong.danh.k@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-31 21:53:00' AS ts
UNION ALL
SELECT 83 AS court_id,'demo.duy.anh.b@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Giá hơi cao một chút, bù lại sân đánh thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-08-24 15:22:00' AS ts
UNION ALL
SELECT 83 AS court_id,'demo.the.vinh.k@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-09-24 08:15:00' AS ts
UNION ALL
SELECT 84 AS court_id,'demo.quoc.bao.l@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-24 21:07:00' AS ts
UNION ALL
SELECT 84 AS court_id,'demo.huu.phuoc.s@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Mức giá mềm so với chất lượng sân. Chắc chắn sẽ quay lại.' AS comment,'2026-08-15 08:45:00' AS ts
UNION ALL
SELECT 84 AS court_id,'demo.thuy.duong.c@demo.godminton.local' AS email,3 AS rating,'Đánh được, nhưng có vài điểm mình thấy cần lưu ý. Nằm ở khu vực Quận 8 (cũ), dễ tìm. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-10-03 13:22:00' AS ts
UNION ALL
SELECT 85 AS court_id,'demo.trung.kien.d@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát.' AS comment,'2026-08-21 14:07:00' AS ts
UNION ALL
SELECT 85 AS court_id,'demo.ha.my.h@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Nằm ở khu vực Quận 8 (cũ), dễ tìm. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-26 19:53:00' AS ts
UNION ALL
SELECT 85 AS court_id,'demo.nhu.quynh.n@demo.godminton.local' AS email,3 AS rating,'Sân tạm ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-09-18 16:53:00' AS ts
UNION ALL
SELECT 86 AS court_id,'demo.to.uyen.d@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Nằm ở khu vực Quận 8 (cũ), dễ tìm. Phòng tắm tiện cho ai đi làm về ghé đánh. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-22 12:22:00' AS ts
UNION ALL
SELECT 86 AS court_id,'demo.trung.kien.v@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Mức giá mềm so với chất lượng sân. Có chỗ để xe, không phải loay hoay tìm chỗ gửi.' AS comment,'2026-08-15 08:38:00' AS ts
UNION ALL
SELECT 86 AS court_id,'demo.dinh.khoi.h@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-16 20:07:00' AS ts
UNION ALL
SELECT 87 AS court_id,'demo.thu.ha.l@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-29 21:45:00' AS ts
UNION ALL
SELECT 87 AS court_id,'demo.kim.ngan.t@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-10-15 14:38:00' AS ts
UNION ALL
SELECT 87 AS court_id,'demo.dinh.khoi.l@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết. Vị trí ở Quận 9 (cũ), đi lại thuận tiện.' AS comment,'2026-09-20 17:53:00' AS ts
UNION ALL
SELECT 88 AS court_id,'demo.trung.kien.v@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Nằm ở khu vực Quận 9 (cũ), dễ tìm. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-24 22:22:00' AS ts
UNION ALL
SELECT 88 AS court_id,'demo.tan.phat.v@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-06 16:45:00' AS ts
UNION ALL
SELECT 88 AS court_id,'demo.hong.nhung.n@demo.godminton.local' AS email,3 AS rating,'Sân tạm ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giờ tối và cuối tuần khá đông, nên đặt trước để có khung giờ đẹp.' AS comment,'2026-09-14 09:53:00' AS ts
UNION ALL
SELECT 91 AS court_id,'demo.quoc.bao.m@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Nằm ở khu vực Quận 10 (cũ), dễ tìm.' AS comment,'2026-08-12 17:53:00' AS ts
UNION ALL
SELECT 91 AS court_id,'demo.ha.my.d@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-09-29 18:22:00' AS ts
UNION ALL
SELECT 91 AS court_id,'demo.hai.yen.m@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng.' AS comment,'2026-08-16 13:22:00' AS ts
UNION ALL
SELECT 92 AS court_id,'demo.tan.phat.m@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Vị trí ở Quận 10 (cũ), đi lại thuận tiện.' AS comment,'2026-08-30 12:45:00' AS ts
UNION ALL
SELECT 92 AS court_id,'demo.duy.anh.h@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-08-23 16:53:00' AS ts
UNION ALL
SELECT 92 AS court_id,'demo.trong.nghia.t@demo.godminton.local' AS email,3 AS rating,'Trải nghiệm ở mức bình thường. Điều hòa giúp đánh lâu mà không bị mệt vì nóng. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-09-24 13:00:00' AS ts
UNION ALL
SELECT 93 AS court_id,'demo.trung.kien.n@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Nằm ở khu vực Quận 11 (cũ), dễ tìm. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-06 15:07:00' AS ts
UNION ALL
SELECT 93 AS court_id,'demo.chi.cong.x@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân.' AS comment,'2026-09-04 18:38:00' AS ts
UNION ALL
SELECT 93 AS court_id,'demo.lan.chi.n@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-10-09 13:38:00' AS ts
UNION ALL
SELECT 98 AS court_id,'demo.trong.nghia.k@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn.' AS comment,'2026-09-11 19:38:00' AS ts
UNION ALL
SELECT 98 AS court_id,'demo.ai.vy.e@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Điều hòa giúp đánh lâu mà không bị mệt vì nóng.' AS comment,'2026-08-11 07:38:00' AS ts
UNION ALL
SELECT 98 AS court_id,'demo.kim.ngan.c@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Nằm ở khu vực Quận 12 (cũ), dễ tìm.' AS comment,'2026-09-22 22:15:00' AS ts
UNION ALL
SELECT 99 AS court_id,'demo.diem.my.h@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Vị trí ở Bình Thạnh (cũ), đi lại thuận tiện. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-09-27 18:38:00' AS ts
UNION ALL
SELECT 99 AS court_id,'demo.trung.kien.e@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-09-26 17:38:00' AS ts
UNION ALL
SELECT 99 AS court_id,'demo.ha.my.g@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-09-23 22:53:00' AS ts
UNION ALL
SELECT 100 AS court_id,'demo.van.hau.a@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Vị trí ở Tân Bình (cũ), đi lại thuận tiện. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-08-06 20:07:00' AS ts
UNION ALL
SELECT 100 AS court_id,'demo.tan.phat.k@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Điều hòa giúp đánh lâu mà không bị mệt vì nóng.' AS comment,'2026-08-23 18:15:00' AS ts
UNION ALL
SELECT 100 AS court_id,'demo.gia.huy.n@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Đánh xong có chỗ tắm nên đỡ phải về nhà mới thay đồ. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-09 21:38:00' AS ts
UNION ALL
SELECT 101 AS court_id,'demo.huu.phuoc.p@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Vị trí ở Tân Bình (cũ), đi lại thuận tiện.' AS comment,'2026-10-10 08:38:00' AS ts
UNION ALL
SELECT 101 AS court_id,'demo.bich.phuong.s@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng.' AS comment,'2026-08-21 17:22:00' AS ts
UNION ALL
SELECT 101 AS court_id,'demo.thanh.tam.t@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-09-29 21:45:00' AS ts
UNION ALL
SELECT 102 AS court_id,'demo.bich.phuong.d@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-11 13:00:00' AS ts
UNION ALL
SELECT 102 AS court_id,'demo.gia.huy.b@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Đánh xong có chỗ tắm nên đỡ phải về nhà mới thay đồ. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Chắc chắn sẽ quay lại.' AS comment,'2026-08-10 10:38:00' AS ts
UNION ALL
SELECT 102 AS court_id,'demo.nhu.quynh.h@demo.godminton.local' AS email,3 AS rating,'Trải nghiệm ở mức bình thường. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giờ tối và cuối tuần khá đông, nên đặt trước để có khung giờ đẹp.' AS comment,'2026-08-08 12:15:00' AS ts
UNION ALL
SELECT 103 AS court_id,'demo.trung.kien.s@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Vị trí ở Gò Vấp (cũ), đi lại thuận tiện. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-22 17:22:00' AS ts
UNION ALL
SELECT 103 AS court_id,'demo.duy.anh.h@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-08-15 07:45:00' AS ts
UNION ALL
SELECT 103 AS court_id,'demo.ha.my.m@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Chắc chắn sẽ quay lại.' AS comment,'2026-08-27 12:53:00' AS ts
UNION ALL
SELECT 104 AS court_id,'demo.kim.ngan.e@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-08-18 19:15:00' AS ts
UNION ALL
SELECT 104 AS court_id,'demo.yen.nhi.v@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Giá hợp lý cho chất lượng nhận được. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-23 17:07:00' AS ts
UNION ALL
SELECT 104 AS court_id,'demo.duc.thinh.e@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Vị trí ở Gò Vấp (cũ), đi lại thuận tiện. Sân có điều hòa, đánh liền mấy set vẫn dễ chịu.' AS comment,'2026-10-06 21:07:00' AS ts
UNION ALL
SELECT 105 AS court_id,'demo.thu.ha.t@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-23 08:22:00' AS ts
UNION ALL
SELECT 105 AS court_id,'demo.phuong.linh.b@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Vị trí ở Gò Vấp (cũ), đi lại thuận tiện. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-09-06 09:00:00' AS ts
UNION ALL
SELECT 105 AS court_id,'demo.hai.yen.t@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Có chỗ để xe, không phải loay hoay tìm chỗ gửi.' AS comment,'2026-08-14 07:22:00' AS ts
UNION ALL
SELECT 106 AS court_id,'demo.huu.phuoc.b@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-08-25 13:00:00' AS ts
UNION ALL
SELECT 106 AS court_id,'demo.yen.nhi.h@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-19 10:53:00' AS ts
UNION ALL
SELECT 106 AS court_id,'demo.ngoc.anh.v@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Có điều hòa nên đánh buổi tối không bị ngột ngạt. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện.' AS comment,'2026-08-26 17:45:00' AS ts
UNION ALL
SELECT 107 AS court_id,'demo.gia.huy.c@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Nằm ở khu vực Phú Nhuận (cũ), dễ tìm. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-25 10:15:00' AS ts
UNION ALL
SELECT 107 AS court_id,'demo.huu.phuoc.l@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-09-25 15:45:00' AS ts
UNION ALL
SELECT 107 AS court_id,'demo.nhat.nam.l@demo.godminton.local' AS email,3 AS rating,'Đánh được, nhưng có vài điểm mình thấy cần lưu ý. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-08-24 17:45:00' AS ts
UNION ALL
SELECT 108 AS court_id,'demo.dang.khoa.p@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Vị trí ở Phú Nhuận (cũ), đi lại thuận tiện. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-09-06 15:45:00' AS ts
UNION ALL
SELECT 108 AS court_id,'demo.thao.nguyen.b@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Giá hợp lý cho chất lượng nhận được. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện. Chắc chắn sẽ quay lại.' AS comment,'2026-10-06 18:15:00' AS ts
UNION ALL
SELECT 108 AS court_id,'demo.cong.danh.h@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-02 09:38:00' AS ts
UNION ALL
SELECT 109 AS court_id,'demo.phuong.linh.a@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-09-03 22:38:00' AS ts
UNION ALL
SELECT 109 AS court_id,'demo.duy.anh.q@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Vị trí ở Thủ Đức (cũ), đi lại thuận tiện. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-10-06 07:38:00' AS ts
UNION ALL
SELECT 109 AS court_id,'demo.dang.khoa.k@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Chắc chắn sẽ quay lại.' AS comment,'2026-10-10 19:07:00' AS ts
UNION ALL
SELECT 110 AS court_id,'demo.thanh.tam.t@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Có chỗ để xe, không phải loay hoay tìm chỗ gửi.' AS comment,'2026-10-05 17:07:00' AS ts
UNION ALL
SELECT 110 AS court_id,'demo.dang.khoa.v@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Phòng tắm tiện cho ai đi làm về ghé đánh. Vị trí ở Thủ Đức (cũ), đi lại thuận tiện.' AS comment,'2026-09-03 11:15:00' AS ts
UNION ALL
SELECT 110 AS court_id,'demo.mai.anh.x@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát.' AS comment,'2026-08-12 11:00:00' AS ts
UNION ALL
SELECT 111 AS court_id,'demo.anh.tu.p@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-10-12 22:15:00' AS ts
UNION ALL
SELECT 111 AS court_id,'demo.the.vinh.a@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Chắc chắn sẽ quay lại.' AS comment,'2026-08-21 08:45:00' AS ts
UNION ALL
SELECT 111 AS court_id,'demo.nhu.quynh.r@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Giá hơi cao một chút, bù lại sân đánh thoải mái. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-28 21:22:00' AS ts
UNION ALL
SELECT 112 AS court_id,'demo.hoang.long.x@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Mức giá mềm so với chất lượng sân.' AS comment,'2026-09-25 13:00:00' AS ts
UNION ALL
SELECT 112 AS court_id,'demo.trong.nghia.n@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Nằm ở khu vực Bình Tân (cũ), dễ tìm. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-10-17 14:22:00' AS ts
UNION ALL
SELECT 112 AS court_id,'demo.ai.vy.x@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng.' AS comment,'2026-08-23 19:38:00' AS ts
UNION ALL
SELECT 113 AS court_id,'demo.ngoc.anh.a@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Vị trí ở Bình Tân (cũ), đi lại thuận tiện.' AS comment,'2026-08-07 15:45:00' AS ts
UNION ALL
SELECT 113 AS court_id,'demo.thanh.tam.k@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-10-02 14:22:00' AS ts
UNION ALL
SELECT 113 AS court_id,'demo.hoang.long.r@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-04 10:53:00' AS ts
UNION ALL
SELECT 115 AS court_id,'demo.nhat.nam.n@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-09 22:15:00' AS ts
UNION ALL
SELECT 115 AS court_id,'demo.quoc.bao.d@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-09-04 14:22:00' AS ts
UNION ALL
SELECT 115 AS court_id,'demo.thanh.tam.t@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Nằm ở khu vực Tân Phú (cũ), dễ tìm.' AS comment,'2026-08-21 20:53:00' AS ts
UNION ALL
SELECT 116 AS court_id,'demo.binh.minh.t@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-08-15 08:53:00' AS ts
UNION ALL
SELECT 116 AS court_id,'demo.thuy.duong.q@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Nằm ở khu vực Tân Phú (cũ), dễ tìm. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-08-10 09:22:00' AS ts
UNION ALL
SELECT 116 AS court_id,'demo.kim.ngan.r@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-10-13 07:38:00' AS ts
UNION ALL
SELECT 117 AS court_id,'demo.quang.vinh.c@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-20 11:38:00' AS ts
UNION ALL
SELECT 117 AS court_id,'demo.ngoc.anh.r@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá hợp lý cho chất lượng nhận được. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-09-05 17:07:00' AS ts
UNION ALL
SELECT 117 AS court_id,'demo.ha.my.l@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-21 21:38:00' AS ts
) v JOIN `courts` c ON c.id=v.court_id JOIN `users` u ON u.email=v.email;
