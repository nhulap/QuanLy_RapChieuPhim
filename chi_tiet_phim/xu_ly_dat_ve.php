<?php
session_start();
require "../Connection.php"; 

// =======================================================
// 1. KIỂM TRA ĐĂNG NHẬP VÀ LẤY DỮ LIỆU ĐẦU VÀO
// =======================================================

// Lấy MaKhachHang từ Session (Đã đăng nhập)
$ma_khach_hang = $_SESSION['user_id'] ?? null; 

// ⭐ KIỂM TRA BẮT BUỘC ĐĂNG NHẬP
if (!$ma_khach_hang) {
    // Nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
    header("Location: ../Login&Register/Login.php");
    exit("Vui lòng đăng nhập để tiếp tục đặt vé.");
}

$ma_suat = $_POST['MaSuatChieu'] ?? null;
$tong_tien = $_POST['TongTien'] ?? 0;
$phuong_thuc = $_POST['PhuongThucThanhToan'] ?? null;
$selected_seats = $_POST['selected_seats'] ?? []; // Mảng chứa [MaGhe1, MaGhe2, ...]
$ma_khuyen_mai = $_POST['MaKhuyenMai'] ?? null;

// Kiểm tra dữ liệu đặt vé còn lại
if (!$ma_suat || $tong_tien <= 0 || !$phuong_thuc || empty($selected_seats)) {
    die("Lỗi: Thiếu thông tin đặt vé hoặc tổng tiền không hợp lệ. Vui lòng quay lại bước thanh toán.");
}

$ma_suat_safe = mysqli_real_escape_string($conn, $ma_suat);
// ⭐ SỬ DỤNG MÃ KHÁCH HÀNG TỪ SESSION
$ma_kh_safe = mysqli_real_escape_string($conn, $ma_khach_hang); 
$tong_tien_safe = (float)$tong_tien;

// =======================================================
// 2. XỬ LÝ TRANSACTION
// =======================================================

// Dùng transaction
mysqli_begin_transaction($conn);

// Tạo Mã Đặt Vé (Tạm thời)
// Sử dụng hàm uniqid để tạo mã đặt vé duy nhất hơn
$ma_dat_ve = 'DV' . uniqid(); 
$current_time = date('Y-m-d H:i:s');
$so_luong_ve = count($selected_seats);
$ghe_da_chon_string = mysqli_real_escape_string($conn, implode(',', $selected_seats)); 

$trang_thai_thanh_toan = 'ThanhCong'; 

try {
    // 2.1. KIỂM TRA ĐỦ SỐ DƯ (TRƯỚC KHI TRỪ TIỀN)
    if ($phuong_thuc == 'TaiKhoan') {
        $sql_check_sodu = "SELECT SoDu FROM khachhang WHERE MaKhachHang = '$ma_kh_safe'";
        $result_sodu = mysqli_query($conn, $sql_check_sodu);
        $kh_info = mysqli_fetch_assoc($result_sodu);

        if (!$kh_info || $kh_info['SoDu'] < $tong_tien_safe) {
             throw new Exception("Lỗi: Số dư tài khoản không đủ để thực hiện thanh toán.");
        }
    }

    // 2.2. THÊM VÀO BẢNG datve
    $sql_insert_datve = "
        INSERT INTO datve (MaDatVe, MaKhachHang, MaKhuyenMai, ThoiGianDat, TongTien, PhuongThucThanhToan, TrangThaiThanhToan, MaSuatChieu, SoLuong, MaGheDaChon)
        VALUES (
            '$ma_dat_ve', 
            '$ma_kh_safe', 
            " . ($ma_khuyen_mai ? "'" . mysqli_real_escape_string($conn, $ma_khuyen_mai) . "'" : "NULL") . ", 
            '$current_time', 
            '$tong_tien_safe', 
            '$phuong_thuc', 
            '$trang_thai_thanh_toan',
            '$ma_suat_safe', 
            '$so_luong_ve', 
            '$ghe_da_chon_string'
        )
    ";
    if (!mysqli_query($conn, $sql_insert_datve)) {
        throw new Exception("Lỗi khi thêm vào bảng datve: " . mysqli_error($conn));
    }

    // 2.3. TRỪ TIỀN KHÁCH HÀNG (Nếu thanh toán bằng tài khoản)
    if ($phuong_thuc == 'TaiKhoan') {
        $sql_update_sodu = "
            UPDATE khachhang 
            SET SoDu = SoDu - $tong_tien_safe 
            WHERE MaKhachHang = '$ma_kh_safe'"; // Không cần kiểm tra SoDu >= trong UPDATE vì đã kiểm tra ở 2.1
            
        if (!mysqli_query($conn, $sql_update_sodu) || mysqli_affected_rows($conn) == 0) {
            // Rollback nếu lỗi trừ tiền
            throw new Exception("Lỗi: Không thể cập nhật số dư tài khoản.");
        }
    }
    
    mysqli_commit($conn); // Hoàn tất Transaction
    
    // Chuyển hướng thành công
    header("Location: dat_ve_thanh_cong.php?MaDatVe=" . urlencode($ma_dat_ve));
    exit();

} catch (Exception $e) {
    // Nếu có lỗi, rollback
    mysqli_rollback($conn);
    die("LỖI ĐẶT VÉ: " . $e->getMessage() . ". Vui lòng thử lại.");
}

mysqli_close($conn); 
?>