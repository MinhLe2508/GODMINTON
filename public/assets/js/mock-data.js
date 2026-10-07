/**
 * GODMINTON - CLIENT-SIDE MOCK DATA ENGINE
 * Dữ liệu mẫu thực tế tại TP. Hồ Chí Minh
 * Hoạt động qua LocalStorage, không cần backend.
 */

/* ============================================================
   COURTS DATA — TP. HỒ CHÍ MINH
   ============================================================ */
const INITIAL_COURTS = [
    {
        id: 1,
        name: "Cầu Lông Quận 1 – Lê Thánh Tôn Sport",
        district: "Quận 1",
        city: "TP. Hồ Chí Minh",
        address: "112 Lê Thánh Tôn, Phường Bến Nghé, Quận 1, TP.HCM",
        min_price: 100000,
        price_per_hour: 100000,
        price_formatted: "100.000 đ",
        image_url: "https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop",
        description: "Tổ hợp sân cầu lông hiện đại ngay trung tâm Quận 1, 6 sân thảm tiêu chuẩn BWF, hệ thống chiếu sáng LED chống lóa. Phù hợp cho người đi làm muốn luyện tập sau giờ hành chính.",
        amenities: ["wifi", "parking", "shower", "aircon", "canteen", "rental"],
        total_sub_courts: 6,
        opening_time: "06:00",
        closing_time: "23:00",
        avg_rating: 4.8,
        total_reviews: 64,
        owner_name: "Nguyễn Quốc Hùng",
        owner_phone: "0909 111 001",
        status: "active",
        reviews: [
            { user_name: "Trần Minh Khoa", rating: 5, comment: "Vị trí trung tâm, tiện đặt sau giờ làm. Sân sạch đẹp!", created_at: "2026-09-15 19:00" },
            { user_name: "Lê Thị Ngọc", rating: 5, comment: "Điều hòa mát, nhân viên thân thiện. Sẽ quay lại!", created_at: "2026-09-13 20:00" },
            { user_name: "Phạm Văn Đức", rating: 4, comment: "Giá hơi cao nhưng chất lượng xứng đáng ở Q1.", created_at: "2026-09-10 18:30" }
        ]
    },
    {
        id: 2,
        name: "CLB Cầu Lông Bình Thạnh – Arena Xô Viết",
        district: "Bình Thạnh",
        city: "TP. Hồ Chí Minh",
        address: "225 Xô Viết Nghệ Tĩnh, Phường 26, Bình Thạnh, TP.HCM",
        min_price: 80000,
        price_per_hour: 80000,
        price_formatted: "80.000 đ",
        image_url: "https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop",
        description: "Sân cầu lông quy mô lớn với 8 sân thảm Yonex cao cấp, dịch vụ căng vợt điện tử lấy ngay trong 30 phút, có phòng VIP riêng tư cho nhóm doanh nghiệp.",
        amenities: ["wifi", "parking", "shower", "aircon", "rental", "locker"],
        total_sub_courts: 8,
        opening_time: "05:30",
        closing_time: "23:00",
        avg_rating: 4.9,
        total_reviews: 91,
        owner_name: "Lê Văn Thành",
        owner_phone: "0909 111 002",
        status: "active",
        reviews: [
            { user_name: "Nguyễn Thị Hà", rating: 5, comment: "Thảm Yonex rất bám, chơi không mỏi chân.", created_at: "2026-09-14 08:00" },
            { user_name: "Võ Minh Tuấn", rating: 5, comment: "Đặt sân nhanh, căng vợt lấy ngay trong 30p. Tuyệt!", created_at: "2026-09-12 17:30" },
            { user_name: "Đặng Thị Kim Ngân", rating: 4, comment: "Sân rộng, bãi xe tiện lợi. Giá tốt cho khu vực này.", created_at: "2026-09-09 20:00" }
        ]
    },
    {
        id: 3,
        name: "Trung Tâm Thể Thao Tân Bình – TBSport",
        district: "Tân Bình",
        city: "TP. Hồ Chí Minh",
        address: "78 Hoàng Văn Thụ, Phường 4, Tân Bình, TP.HCM",
        min_price: 75000,
        price_per_hour: 75000,
        price_formatted: "75.000 đ",
        image_url: "https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop",
        description: "Nằm gần sân bay Tân Sơn Nhất, 10 sân thảm tiêu chuẩn thi đấu, phù hợp cho câu lạc bộ và giải phong trào. Có khu vực ngồi chờ rộng rãi và quầy đồ uống thể thao.",
        amenities: ["wifi", "parking", "shower", "canteen", "rental"],
        total_sub_courts: 10,
        opening_time: "05:00",
        closing_time: "23:30",
        avg_rating: 4.7,
        total_reviews: 48,
        owner_name: "Phạm Thị Lan",
        owner_phone: "0909 111 003",
        status: "active",
        reviews: [
            { user_name: "Hồ Văn Linh", rating: 5, comment: "Nhiều sân, ít khi phải chờ. Thích hợp đặt buổi sáng sớm.", created_at: "2026-09-16 06:00" },
            { user_name: "Trương Thị Bé", rating: 4, comment: "Giá tốt, sân sạch. Vị trí gần trung tâm.", created_at: "2026-09-11 18:00" }
        ]
    },
    {
        id: 4,
        name: "Sân Cầu Lông Gò Vấp – GV Arena",
        district: "Gò Vấp",
        city: "TP. Hồ Chí Minh",
        address: "350 Nguyễn Kiệm, Phường 3, Gò Vấp, TP.HCM",
        min_price: 70000,
        price_per_hour: 70000,
        price_formatted: "70.000 đ",
        image_url: "https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop",
        description: "Sân cầu lông gia đình thân thiện, 5 sân thảm Victor cao su tự nhiên, thích hợp cho mọi lứa tuổi. Có khu vực vui chơi trẻ em và phòng nghỉ ngơi sau khi chơi.",
        amenities: ["wifi", "parking", "shower", "aircon", "canteen"],
        total_sub_courts: 5,
        opening_time: "06:00",
        closing_time: "22:30",
        avg_rating: 4.6,
        total_reviews: 37,
        owner_name: "Nguyễn Văn Minh",
        owner_phone: "0909 111 004",
        status: "active",
        reviews: [
            { user_name: "Bùi Thị Mai", rating: 5, comment: "Sân thân thiện với gia đình, trẻ em có chỗ chơi an toàn.", created_at: "2026-09-13 09:00" },
            { user_name: "Lý Văn Hùng", rating: 4, comment: "Thảm Victor rất tốt, giá mềm nhất khu vực.", created_at: "2026-09-08 19:30" }
        ]
    },
    {
        id: 5,
        name: "Cầu Lông Quận 7 – Phú Mỹ Hưng Sport",
        district: "Quận 7",
        city: "TP. Hồ Chí Minh",
        address: "Block B2, Khu đô thị Phú Mỹ Hưng, Quận 7, TP.HCM",
        min_price: 120000,
        price_per_hour: 120000,
        price_formatted: "120.000 đ",
        image_url: "https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&auto=format&fit=crop",
        description: "Sân cầu lông cao cấp trong khu đô thị Phú Mỹ Hưng, 4 sân thảm Li-Ning nhập khẩu, có đội ngũ HLV chuyên nghiệp từ đội tuyển quốc gia. Dịch vụ 5 sao, phòng tắm xông hơi sau tập.",
        amenities: ["wifi", "parking", "shower", "aircon", "canteen", "rental", "locker"],
        total_sub_courts: 4,
        opening_time: "06:00",
        closing_time: "22:00",
        avg_rating: 5.0,
        total_reviews: 28,
        owner_name: "Trần Thị Thu Hương",
        owner_phone: "0909 111 005",
        status: "active",
        reviews: [
            { user_name: "Đỗ Minh Nhật", rating: 5, comment: "Sang trọng, chuyên nghiệp. Xứng đáng với giá tiền.", created_at: "2026-09-14 20:00" },
            { user_name: "Nguyễn Thị Thảo", rating: 5, comment: "HLV giỏi, tận tình. Học rất tiến bộ sau 1 tháng.", created_at: "2026-09-10 18:00" }
        ]
    },
    {
        id: 6,
        name: "CLB Thể Thao Thủ Đức – ThủĐức Arena",
        district: "TP. Thủ Đức",
        city: "TP. Hồ Chí Minh",
        address: "45 Võ Văn Ngân, Phường Bình Thọ, TP. Thủ Đức, TP.HCM",
        min_price: 65000,
        price_per_hour: 65000,
        price_formatted: "65.000 đ",
        image_url: "https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop",
        description: "Nằm gần ĐH Bách Khoa và ĐH Quốc Gia, là địa điểm yêu thích của sinh viên và giảng viên. 7 sân thảm giá sinh viên, mở cửa sớm từ 5 giờ sáng phục vụ buổi tập sáng.",
        amenities: ["wifi", "parking", "shower", "rental", "canteen"],
        total_sub_courts: 7,
        opening_time: "05:00",
        closing_time: "23:00",
        avg_rating: 4.5,
        total_reviews: 112,
        owner_name: "Vũ Văn Đạt",
        owner_phone: "0909 111 006",
        status: "active",
        reviews: [
            { user_name: "Nguyễn Văn Sinh", rating: 5, comment: "Giá sinh viên cực ưu đãi, 5h sáng đã mở cửa. Top 1!", created_at: "2026-09-16 05:30" },
            { user_name: "Trần Thị Hoa", rating: 4, comment: "Sân rộng, nhiều người chơi. Đặt sẵn trước để không phải chờ.", created_at: "2026-09-12 07:00" },
            { user_name: "Lê Quốc Bảo", rating: 5, comment: "Giảng viên chúng tôi hay đặt ở đây, rất tiện.", created_at: "2026-09-08 19:00" }
        ]
    },
    {
        id: 7,
        name: "Sân Cầu Lông Bình Dương – BD Sport Center",
        district: "Thuận An",
        city: "Bình Dương",
        address: "234 Đại lộ Bình Dương, Phường Bình Chuẩn, Thuận An, Bình Dương",
        min_price: 60000,
        price_per_hour: 60000,
        price_formatted: "60.000 đ",
        image_url: "https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop",
        description: "Sân cầu lông lớn nhất Bình Dương với 12 sân thảm tiêu chuẩn, sân gần khu công nghiệp VSIP tiện cho công nhân viên sau giờ làm. Có phòng gym và bể bơi đi kèm.",
        amenities: ["wifi", "parking", "shower", "aircon", "canteen", "rental", "locker"],
        total_sub_courts: 12,
        opening_time: "05:30",
        closing_time: "23:00",
        avg_rating: 4.7,
        total_reviews: 76,
        owner_name: "Hoàng Văn Nam",
        owner_phone: "0909 111 007",
        status: "active",
        reviews: [
            { user_name: "Công Nhân VSIP", rating: 5, comment: "Gần khu công nghiệp, tiện ghé sau ca làm.", created_at: "2026-09-15 21:00" },
            { user_name: "Trần Văn Phúc", rating: 4, comment: "Sân nhiều, ít khi chờ. Giá bình dân.", created_at: "2026-09-11 18:30" }
        ]
    }
];

/* ============================================================
   NEWS DATA — TP. HỒ CHÍ MINH
   ============================================================ */
const INITIAL_NEWS = [
    {
        id: 1,
        title: "Giải Cầu Lông Mở Rộng TP.HCM Open 2026 – Tổng Giải Thưởng 100 Triệu Đồng",
        summary: "Giải cầu lông phong trào lớn nhất TP.HCM năm 2026 chính thức mở đăng ký với tổng giải thưởng lên đến 100 triệu đồng. Các nội dung thi đấu đa dạng từ hạng A đến hạng D cho mọi trình độ.",
        content: "<p>Giải Cầu Lông Mở Rộng TP.HCM Open 2026 sẽ được tổ chức từ ngày 15 đến 20/10/2026 tại Nhà Thi Đấu Phú Thọ, Quận 11. Các nội dung thi đấu gồm: đơn nam, đơn nữ, đôi nam, đôi nữ, đôi nam nữ ở hạng A, B, C, D.</p><p>Đăng ký online tại GODMINTON hoặc liên hệ hotline 1900 8888. Hạn đăng ký: 30/09/2026.</p>",
        image_url: "https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&auto=format&fit=crop",
        created_at: "2026-09-18 08:00"
    },
    {
        id: 2,
        title: "Top 5 Sân Cầu Lông Đẹp Nhất TP.HCM Nên Thử Một Lần",
        summary: "Từ sân cao cấp ở Phú Mỹ Hưng đến sân bình dân giá sinh viên ở Thủ Đức, GODMINTON tổng hợp 5 địa điểm cầu lông được đánh giá cao nhất tại TP.HCM năm 2026.",
        content: "<p>TP.HCM có hàng trăm sân cầu lông lớn nhỏ, nhưng những sân dưới đây nổi bật về chất lượng và dịch vụ.</p><p><strong>1. Phú Mỹ Hưng Sport</strong> – Sang trọng, HLV chuyên nghiệp.<br><strong>2. Arena Xô Viết (Bình Thạnh)</strong> – Thảm Yonex, 8 sân, giá hợp lý.<br><strong>3. Lê Thánh Tôn Sport (Q.1)</strong> – Ngay trung tâm, tiện nhất.<br><strong>4. TBSport (Tân Bình)</strong> – Gần sân bay, 10 sân, mở cửa sớm.<br><strong>5. ThủĐức Arena</strong> – Rẻ nhất, dành cho sinh viên.</p>",
        image_url: "https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=600&auto=format&fit=crop",
        created_at: "2026-09-16 09:00"
    },
    {
        id: 3,
        title: "Mẹo Chọn Vợt Cầu Lông Phù Hợp Với Khí Hậu Nóng Ẩm TP.HCM",
        summary: "Khí hậu nóng ẩm TP.HCM ảnh hưởng nhiều đến lực đánh và độ bền dây vợt. Chuyên gia GODMINTON chia sẻ cách chọn vợt và dây căng phù hợp để tối ưu hiệu suất thi đấu.",
        content: "<p>Tại TP.HCM với độ ẩm cao trên 80%, dây vợt dễ giãn hơn ở vùng khô. Nên căng dây ở lực cao hơn 1-2 lbs so với khuyến nghị của nhà sản xuất.</p><p>Vợt nhẹ (dưới 85g) phù hợp vì người chơi ra mồ hôi nhiều, tay dễ trơn. Nên dùng quấn cán có khả năng thấm hút mồ hôi tốt như Yonex AC102 hoặc Victor GR262.</p>",
        image_url: "https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=600&auto=format&fit=crop",
        created_at: "2026-09-14 10:00"
    },
    {
        id: 4,
        title: "Khai Trương Sân Cầu Lông Phú Mỹ Hưng Sport – Ưu Đãi 30% Tháng Đầu",
        summary: "Sân cầu lông cao cấp Phú Mỹ Hưng Sport chính thức khai trương với cơ sở vật chất 5 sao. Tháng khai trương ưu đãi 30% cho tất cả khách hàng đặt sân qua GODMINTON.",
        content: "<p>Phú Mỹ Hưng Sport – sân cầu lông đẳng cấp nhất TP.HCM chính thức mở cửa từ 01/10/2026. Trang bị 4 sân thảm Li-Ning nhập khẩu từ Trung Quốc, đèn LED Philips Sports chuyên dụng, phòng thay đồ VIP.</p><p>Ưu đãi khai trương: Giảm 30% khi đặt qua GODMINTON trong tháng 10/2026.</p>",
        image_url: "https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=600&auto=format&fit=crop",
        created_at: "2026-09-12 11:00"
    }
];

/* ============================================================
   INITIAL BOOKINGS DATA
   ============================================================ */
const INITIAL_BOOKINGS = [
    {
        id: 1,
        booking_code: "GDM-20260918-0001",
        court_id: 1,
        court_name: "Cầu Lông Quận 1 – Lê Thánh Tôn Sport",
        court_address: "112 Lê Thánh Tôn, Quận 1, TP.HCM",
        court_district: "Quận 1",
        sub_court_name: "Sân A1",
        booking_date: new Date().toISOString().split('T')[0],
        start_time: "18:00",
        end_time: "19:30",
        time_range: "18:00 - 19:30",
        total_amount: 150000,
        deposit_amount: 75000,
        payment_method: "VNPay",
        booking_status: "confirmed",
        user_name: "Nguyễn Văn An",
        user_phone: "0912345678",
        created_at: "2026-09-18 10:30:00"
    },
    {
        id: 2,
        booking_code: "GDM-20260916-0002",
        court_id: 2,
        court_name: "CLB Cầu Lông Bình Thạnh – Arena Xô Viết",
        court_address: "225 Xô Viết Nghệ Tĩnh, Bình Thạnh, TP.HCM",
        court_district: "Bình Thạnh",
        sub_court_name: "Sân B2",
        booking_date: "2026-09-16",
        start_time: "07:00",
        end_time: "08:30",
        time_range: "07:00 - 08:30",
        total_amount: 120000,
        deposit_amount: 60000,
        payment_method: "MoMo",
        booking_status: "completed",
        user_name: "Nguyễn Văn An",
        user_phone: "0912345678",
        created_at: "2026-09-15 21:00:00"
    },
    {
        id: 3,
        booking_code: "GDM-20260910-0003",
        court_id: 6,
        court_name: "CLB Thể Thao Thủ Đức – ThủĐức Arena",
        court_address: "45 Võ Văn Ngân, TP. Thủ Đức, TP.HCM",
        court_district: "TP. Thủ Đức",
        sub_court_name: "Sân C3",
        booking_date: "2026-09-10",
        start_time: "06:00",
        end_time: "07:00",
        time_range: "06:00 - 07:00",
        total_amount: 65000,
        deposit_amount: 32500,
        payment_method: "Chuyển khoản",
        booking_status: "cancelled",
        user_name: "Nguyễn Văn An",
        user_phone: "0912345678",
        created_at: "2026-09-09 20:00:00"
    }
];

/* ============================================================
   MockDB CLASS
   ============================================================ */
class MockDB {

    /* ---- Khởi tạo dữ liệu lần đầu ---- */
    static init() {
        if (!localStorage.getItem('bm_courts')) {
            localStorage.setItem('bm_courts', JSON.stringify(INITIAL_COURTS));
        }
        if (!localStorage.getItem('bm_bookings')) {
            localStorage.setItem('bm_bookings', JSON.stringify(INITIAL_BOOKINGS));
        }
        if (!localStorage.getItem('bm_news')) {
            localStorage.setItem('bm_news', JSON.stringify(INITIAL_NEWS));
        }
        if (!localStorage.getItem('bm_user')) {
            localStorage.setItem('bm_user', JSON.stringify({
                id: 4,
                name: "Nguyễn Văn An",
                email: "khach1@gmail.com",
                phone: "0912345678",
                role: "customer"
            }));
        }
    }

    /* ---- Lấy danh sách sân (có lọc) ---- */
    static getCourts(filters = {}) {
        this.init();
        let courts = JSON.parse(localStorage.getItem('bm_courts') || '[]');
        if (filters.search) {
            const q = filters.search.toLowerCase();
            courts = courts.filter(c =>
                c.name.toLowerCase().includes(q) || c.address.toLowerCase().includes(q));
        }
        if (filters.district) {
            courts = courts.filter(c => c.district === filters.district);
        }
        if (filters.max_price) {
            courts = courts.filter(c => c.price_per_hour <= parseInt(filters.max_price));
        }
        if (filters.amenity) {
            courts = courts.filter(c => c.amenities && c.amenities.includes(filters.amenity));
        }
        return courts;
    }

    static getCourtById(id) {
        this.init();
        const courts = JSON.parse(localStorage.getItem('bm_courts') || '[]');
        return courts.find(c => c.id === parseInt(id)) || courts[0];
    }

    /* ---- User management ---- */
    static getUser() {
        this.init();
        const str = localStorage.getItem('bm_user');
        return str ? JSON.parse(str) : null;
    }

    static setUser(user) {
        if (user) {
            localStorage.setItem('bm_user', JSON.stringify(user));
        } else {
            localStorage.removeItem('bm_user');
        }
    }

    /* ---- News ---- */
    static getNews() {
        this.init();
        return JSON.parse(localStorage.getItem('bm_news') || '[]');
    }

    /* ---- Bookings ---- */
    static getBookings() {
        this.init();
        return JSON.parse(localStorage.getItem('bm_bookings') || '[]');
    }

    static saveBookings(bookings) {
        localStorage.setItem('bm_bookings', JSON.stringify(bookings));
    }

    static createBooking(data) {
        const bookings = this.getBookings();
        const dateCode = (data.booking_date || '').replace(/-/g, '');
        const rand = Math.floor(1000 + Math.random() * 9000);
        const newBooking = {
            id: Date.now(),
            booking_code: `GDM-${dateCode}-${rand}`,
            court_id:       data.court_id,
            court_name:     data.court_name,
            court_address:  data.court_address || '',
            court_district: data.court_district || '',
            sub_court_name: data.sub_court_name || 'Sân 1',
            booking_date:   data.booking_date,
            start_time:     data.start_time,
            end_time:       data.end_time,
            time_range:     `${data.start_time} - ${data.end_time}`,
            total_amount:   data.total_price  || 0,
            deposit_amount: data.deposit_paid || 0,
            payment_method: data.payment_method || 'VNPay',
            booking_status: data.booking_status || 'confirmed',
            notes:          data.notes || '',
            user_name:      this.getUser()?.name  || '',
            user_phone:     this.getUser()?.phone || '',
            created_at:     new Date().toISOString().replace('T',' ').substring(0,19)
        };
        bookings.unshift(newBooking);
        this.saveBookings(bookings);
        return { status: 'success', data: newBooking };
    }

    /* ---- Format tiền VND ---- */
    static formatVND(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
    }

    /* ---- Slots (grid đặt sân) ---- */
    static getSlotsForCourt(courtId, dateStr) {
        this.init();
        const court      = this.getCourtById(courtId);
        const subCourts  = ['Sân 1', 'Sân 2', 'Sân 3'];
        const hours = [
            ['06:00','07:00'], ['07:00','08:00'], ['08:00','09:00'],
            ['09:00','10:00'], ['10:00','11:00'], ['14:00','15:00'],
            ['15:00','16:00'], ['16:00','17:00'], ['17:00','18:00'],
            ['18:00','19:00'], ['19:00','20:00'], ['20:00','21:00'],
            ['21:00','22:00']
        ];

        const storeKey   = `bm_slots_${courtId}_${dateStr}`;
        let storedSlots  = JSON.parse(localStorage.getItem(storeKey) || 'null');
        const now        = Date.now();
        const currentUser = this.getUser();

        if (!storedSlots) {
            storedSlots = [];
            let idCounter = courtId * 1000;
            subCourts.forEach(sub => {
                hours.forEach(([start, end]) => {
                    idCounter++;
                    let status = 'available';
                    // Pre-seed một số slot bận để demo
                    if (sub === 'Sân 1' && (start === '18:00' || start === '19:00')) status = 'booked';
                    if (sub === 'Sân 2' && start === '07:00') status = 'booked';

                    storedSlots.push({
                        id: idCounter,
                        court_id: courtId,
                        court_name: court.name,
                        sub_court_name: sub,
                        slot_date: dateStr,
                        start_time: start,
                        end_time: end,
                        time_range: `${start} - ${end}`,
                        // Giờ cao điểm chiều tối thêm 15.000đ
                        price: (parseInt(start) >= 17 && parseInt(start) <= 21)
                            ? court.price_per_hour + 15000
                            : court.price_per_hour,
                        status: status,
                        holding_user_id: null,
                        hold_expire: null
                    });
                });
            });
            localStorage.setItem(storeKey, JSON.stringify(storedSlots));
        }

        // Cập nhật trạng thái hold hết hạn
        let updated = false;
        storedSlots.forEach(slot => {
            if (slot.status === 'holding' && slot.hold_expire && now > slot.hold_expire) {
                slot.status = 'available';
                slot.holding_user_id = null;
                slot.hold_expire = null;
                updated = true;
            }
            slot.price_formatted = this.formatVND(slot.price);
            slot.is_my_hold = (currentUser && slot.status === 'holding' && slot.holding_user_id === currentUser.id);
            slot.hold_seconds_left = slot.hold_expire ? Math.max(0, Math.floor((slot.hold_expire - now) / 1000)) : 0;
        });
        if (updated) localStorage.setItem(storeKey, JSON.stringify(storedSlots));

        return storedSlots;
    }

    static getSlotById(courtId, dateStr, slotId) {
        return this.getSlotsForCourt(courtId, dateStr).find(s => s.id === parseInt(slotId));
    }

    /* ---- Giữ chỗ (Hold) ---- */
    static holdSlot(courtId, dateStr, slotId) {
        const storeKey    = `bm_slots_${courtId}_${dateStr}`;
        const slots       = this.getSlotsForCourt(courtId, dateStr);
        const slot        = slots.find(s => s.id === parseInt(slotId));
        const currentUser = this.getUser();

        if (!currentUser) return { status: 'error', code: 401, message: 'Vui lòng đăng nhập để đặt sân.' };
        if (!slot)        return { status: 'error', code: 404, message: 'Không tìm thấy khung giờ.' };

        const now          = Date.now();
        const isAvailable  = slot.status === 'available';
        const isExpired    = slot.status === 'holding' && slot.hold_expire && now > slot.hold_expire;
        const isMyHold     = slot.status === 'holding' && slot.holding_user_id === currentUser.id;

        if (!isAvailable && !isExpired && !isMyHold) {
            return { status: 'error', code: 409, message: 'Khung giờ đã được người khác giữ chỗ. Vui lòng chọn khung giờ khác!' };
        }

        slot.status          = 'holding';
        slot.holding_user_id = currentUser.id;
        slot.hold_expire     = now + 5 * 60 * 1000; // 5 phút
        localStorage.setItem(storeKey, JSON.stringify(slots));

        return {
            status: 'success', code: 200,
            message: 'Giữ chỗ thành công! Bạn có 5 phút để thanh toán.',
            data: {
                slot_id: slot.id, court_id: courtId, slot_date: dateStr,
                sub_court_name: slot.sub_court_name,
                start_time: slot.start_time, end_time: slot.end_time,
                price: slot.price, price_formatted: this.formatVND(slot.price),
                expires_in: 300
            }
        };
    }

    /* ---- Xác nhận đặt sân ---- */
    static confirmBooking(courtId, dateStr, slotId, paymentMethod = 'VNPay', notes = '') {
        const storeKey    = `bm_slots_${courtId}_${dateStr}`;
        const slots       = this.getSlotsForCourt(courtId, dateStr);
        const slot        = slots.find(s => s.id === parseInt(slotId));
        const currentUser = this.getUser();

        if (!slot || slot.status !== 'holding' || slot.holding_user_id !== currentUser?.id) {
            return { status: 'error', message: 'Phiên giữ chỗ không hợp lệ hoặc đã hết hạn.' };
        }

        slot.status      = 'booked';
        slot.hold_expire = null;
        localStorage.setItem(storeKey, JSON.stringify(slots));

        const court        = this.getCourtById(courtId);
        const depositAmount = Math.round(slot.price * 0.5);
        const dateCode     = dateStr.replace(/-/g, '');
        const rand         = Math.floor(1000 + Math.random() * 9000);

        const newBooking = {
            id: Date.now(),
            booking_code:   `GDM-${dateCode}-${rand}`,
            court_id:       slot.court_id,
            court_name:     slot.court_name,
            court_address:  court?.address || '',
            court_district: court?.district || '',
            sub_court_name: slot.sub_court_name,
            booking_date:   slot.slot_date,
            start_time:     slot.start_time,
            end_time:       slot.end_time,
            time_range:     slot.time_range,
            total_amount:   slot.price,
            deposit_amount: depositAmount,
            payment_method: paymentMethod,
            booking_status: 'confirmed',
            notes:          notes,
            user_name:      currentUser.name,
            user_phone:     currentUser.phone,
            created_at:     new Date().toISOString().replace('T',' ').substring(0,19)
        };

        const bookings = this.getBookings();
        bookings.unshift(newBooking);
        this.saveBookings(bookings);

        return {
            status: 'success',
            data: { ...newBooking, deposit_formatted: this.formatVND(depositAmount), total_formatted: this.formatVND(slot.price) }
        };
    }

    /* ---- Hủy slot / đơn ---- */
    static cancelSlot(courtId, dateStr, slotId) {
        const storeKey = `bm_slots_${courtId}_${dateStr}`;
        const slots    = this.getSlotsForCourt(courtId, dateStr);
        const slot     = slots.find(s => s.id === parseInt(slotId));
        if (slot) {
            slot.status = 'available'; slot.holding_user_id = null; slot.hold_expire = null;
            localStorage.setItem(storeKey, JSON.stringify(slots));
            return { status: 'success', message: 'Đã hủy giữ chỗ.' };
        }
        return { status: 'error', message: 'Không tìm thấy slot.' };
    }

    static cancelBooking(bookingId) {
        const bookings = this.getBookings();
        const booking  = bookings.find(b => b.id === parseInt(bookingId));
        if (booking) {
            booking.booking_status = 'cancelled';
            this.saveBookings(bookings);
            return { status: 'success', message: 'Đã hủy đơn đặt sân.' };
        }
        return { status: 'error', message: 'Không tìm thấy đơn.' };
    }

    /* ---- Đăng nhập ---- */
    static login(email, password) {
        const DEMO_USERS = [
            { id: 4, name: "Nguyễn Văn An",  email: "khach1@gmail.com",          phone: "0912 345 678", role: "customer", password: "Khach@123" },
            { id: 5, name: "Trần Thị Bình",  email: "khach2@gmail.com",          phone: "0909 876 543", role: "customer", password: "Khach@456" },
            { id: 1, name: "Quản Trị Viên",  email: "admin@godminton.vn",        phone: "0900 000 001", role: "admin",    password: "Admin@123" },
            { id: 2, name: "Chủ Sân Q.1",    email: "owner.q1@godminton.vn",     phone: "0909 111 001", role: "owner",    password: "Owner@123" }
        ];
        const user = DEMO_USERS.find(u => u.email === email && u.password === password);
        if (user) {
            const { password: _, ...safeUser } = user;
            this.setUser(safeUser);
            return { status: 'success', data: { user: safeUser } };
        }
        return { status: 'error', message: 'Email hoặc mật khẩu không chính xác.' };
    }

    /* ---- Đăng ký ---- */
    static register(data) {
        const { name, email, phone, password, role } = data;
        if (!name || !email || !phone || !password) {
            return { status: 'error', message: 'Vui lòng điền đầy đủ thông tin.' };
        }
        const newUser = { id: Date.now(), name, email, phone, role: role || 'customer' };
        this.setUser(newUser);
        return { status: 'success', data: { user: newUser } };
    }

    /* ---- Đăng xuất ---- */
    static logout() { this.setUser(null); }

    /* ---- Gửi liên hệ ---- */
    static sendContact(data) {
        const contacts = JSON.parse(localStorage.getItem('bm_contacts') || '[]');
        contacts.unshift({ ...data, id: Date.now(), created_at: new Date().toISOString() });
        localStorage.setItem('bm_contacts', JSON.stringify(contacts));
        return { status: 'success' };
    }
}

// Xóa cache cũ để load data TP.HCM mới
(function resetToHCMC() {
    localStorage.removeItem('bm_courts');
    localStorage.removeItem('bm_news');
    localStorage.removeItem('bm_bookings');
    // Xóa cache slots cũ
    Object.keys(localStorage).filter(k => k.startsWith('bm_slots_')).forEach(k => localStorage.removeItem(k));
})();

MockDB.init();
window.MockDB = MockDB;
