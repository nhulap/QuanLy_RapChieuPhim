<?php
session_start();
require "../Connection.php"; 

// --- PHẦN 1: KIỂM TRA ĐĂNG NHẬP VÀ PHƯƠNG THỨC POST ---

// 1. Kiểm tra và gán MaKhachHang từ Session
if (!isset($_SESSION['user_id'])) {
    // ⭐ QUAN TRỌNG: Chuyển hướng người dùng chưa đăng nhập ⭐
    // Tùy chọn: Lưu lại URL để chuyển hướng quay lại sau khi đăng nhập
    $_SESSION['redirect_url'] = '../datphong/datphong.php'; 
    header("Location: ../Login&Register/Login.php");
    exit;
} else {
    $ma_khach_hang = $_SESSION['user_id'];
}

// 2. Kiểm tra phương thức POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: datphong.php");
    exit;
}

// --- PHẦN 2: LẤY VÀ LÀM SẠCH DỮ LIỆU ---

$ma_phong = $_POST['MaPhong'] ?? '';
// ⭐ CỘT MỚI: Lấy MaPhim và xử lý NULL ⭐
$ma_phim_raw = $_POST['MaPhim'] ?? 'none';
$thoi_gian_bat_dau_str = $_POST['ThoiGianBatDau'] ?? '';
$thoi_gian_ket_thuc_str = $_POST['ThoiGianKetThuc'] ?? '';
$tong_tien = $_POST['TongTien'] ?? 0.00; 
$muc_dich_thue = $_POST['MucDichThue'] ?? 'Sự kiện riêng';

if (empty($ma_phong) || empty($thoi_gian_bat_dau_str) || empty($thoi_gian_ket_thuc_str) || $tong_tien <= 0) {
    die("Lỗi: Dữ liệu đặt phòng không hợp lệ.");
}

// Làm sạch dữ liệu
$ma_phong_safe = mysqli_real_escape_string($conn, $ma_phong);
$thoi_gian_bat_dau_safe = mysqli_real_escape_string($conn, $thoi_gian_bat_dau_str);
$thoi_gian_ket_thuc_safe = mysqli_real_escape_string($conn, $thoi_gian_ket_thuc_str);
$muc_dich_thue_safe = mysqli_real_escape_string($conn, $muc_dich_thue);
$ma_khach_hang_safe = mysqli_real_escape_string($conn, $ma_khach_hang);
$tong_tien_safe = (float)$tong_tien;

// ⭐ Xử lý MaPhim để chèn 'NULL' hoặc 'Giá trị có dấu nháy đơn' ⭐
if ($ma_phim_raw === 'none' || empty($ma_phim_raw)) {
    $ma_phim_safe = 'NULL'; 
} else {
    $ma_phim_safe = "'" . mysqli_real_escape_string($conn, $ma_phim_raw) . "'";
}


// --- PHẦN 3: TẠO MÃ VÀ KIỂM TRA XUNG ĐỘT LỊCH ---

// 3. Tạo Mã Đặt Phòng Duy Nhất
$sql_max_id = "SELECT MAX(MaDatPhong) AS MaxID FROM datphongthue";
$result_max_id = mysqli_query($conn, $sql_max_id);
$row_max_id = mysqli_fetch_assoc($result_max_id);
$last_id = $row_max_id['MaxID'];

if ($last_id) {
    $number = (int)substr($last_id, 2) + 1;
} else {
    $number = 1;
}
$new_id = 'DP' . str_pad($number, 8, '0', STR_PAD_LEFT);


// 4. KIỂM TRA XUNG ĐỘT LỊCH LẦN CUỐI
$sql_check_sc = "SELECT MaSuatChieu FROM suatchieu 
                 WHERE MaPhong = '$ma_phong_safe' 
                 AND ThoiGianBatDau < '$thoi_gian_ket_thuc_safe' 
                 AND DATE_ADD(ThoiGianBatDau, INTERVAL 3 HOUR) > '$thoi_gian_bat_dau_safe'";
$result_check_sc = mysqli_query($conn, $sql_check_sc);

// Kiểm tra với các đơn thuê phòng khác đang 'Pending' hoặc 'Approved' (thanh toán/chưa thanh toán)
$sql_check_dpt = "SELECT MaDatPhong FROM datphongthue 
                  WHERE MaPhong = '$ma_phong_safe' AND TrangThaiXacNhan IN ('Pending', 'Approved')
                  AND ThoiGianBatDau < '$thoi_gian_ket_thuc_safe' 
                  AND ThoiGianKetThuc > '$thoi_gian_bat_dau_safe'";
$result_check_dpt = mysqli_query($conn, $sql_check_dpt);

if (mysqli_num_rows($result_check_sc) > 0 || mysqli_num_rows($result_check_dpt) > 0) {
    die("Lỗi Đặt Phòng: Phòng VIP đã bị thuê/chiếu trong khoảng thời gian này.");
}


// --- PHẦN 4: LƯU VÀO DB VÀ THÔNG BÁO ---

// 5. THỰC HIỆN LƯU VÀO CƠ SỞ DỮ LIỆU
// ⭐ SỬA: Thêm MaPhim, TrangThaiXacNhan = 'Pending' (Mới) ⭐
$sql_insert = "INSERT INTO datphongthue (MaDatPhong, MaKhachHang, MaPhong, MaPhim, ThoiGianBatDau, ThoiGianKetThuc, TongTienThue, MucDichThue, TrangThaiXacNhan, TrangThaiThanhToan)
               VALUES ('$new_id', '$ma_khach_hang_safe', '$ma_phong_safe', $ma_phim_safe, '$thoi_gian_bat_dau_safe', '$thoi_gian_ket_thuc_safe', $tong_tien_safe, '$muc_dich_thue_safe', 'Pending', 'ChuaThanhToan')";

if (mysqli_query($conn, $sql_insert)) {
    
    // ⭐ THÀNH CÔNG: Hiển thị thông báo chờ xác nhận ⭐
    $_SESSION['datphong_id'] = $new_id;
    
    mysqli_close($conn);

    $tong_tien_format = number_format($tong_tien_safe, 0, ',', '.');
    
    echo "
    <!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Gửi Yêu Cầu Thành Công</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding-top: 50px; background-color: #f0f0f0; }
            .success-box { max-width: 700px; margin: 0 auto; padding: 30px; border: 2px solid #007bff; border-radius: 8px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            h2 { color: #007bff; }
            p { font-size: 1.1em; color: #555; }
            .status { font-size: 1.5em; color: orange; font-weight: bold; margin: 15px 0; border: 1px dashed orange; padding: 10px; background-color: #fff3e0; }
            .total { font-size: 1.3em; color: #333; margin-bottom: 20px; }
            a { color: #d11e3b; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='success-box'>
            <h2>✨ GỬI YÊU CẦU ĐẶT PHÒNG THÀNH CÔNG!</h2>
            <p>Mã đơn: **{$new_id}**.</p>
            <div class='total'>Tổng tiền tạm tính: **{$tong_tien_format} VND**</div>
            
            <div class='status'>
                ⚠️ Yêu cầu của bạn đang **CHỜ XÁC NHẬN** từ Ban Quản Lý.
                <br>
                Bạn có thể chọn thanh toán ngay để ưu tiên giữ chỗ (tiền sẽ được hoàn nếu yêu cầu bị từ chối), hoặc chờ xác nhận trước khi thanh toán.
            </div>

            <p>
                <a href='../thanhtoan/thanh_toan.php?order=$new_id'>👉 TIẾN HÀNH THANH TOÁN NGAY</a> 
                | 
                <a href='../Index.php'>QUAY LẠI TRANG CHỦ</a>
            </p>
        </div>
    </body>
    </html>";
    exit;
    
} else {
    $error = "Lỗi khi lưu thông tin đặt phòng vào hệ thống: " . mysqli_error($conn);
    mysqli_close($conn);
    die($error);
}
?>