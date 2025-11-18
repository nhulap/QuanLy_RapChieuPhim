<?php
session_start();
require "../Connection.php"; 

$ma_suat = $_POST['MaSuatChieu'] ?? die("Thiếu Mã Suất Chiếu.");
$selected_seats = $_POST['selected_seats'] ?? [];

if (empty($selected_seats)) {
    die("Vui lòng chọn ít nhất một ghế.");
}

$ma_khach_hang = $_SESSION['MaKhachHang'] ?? 'GUEST'; 
if ($ma_khach_hang === 'GUEST') {
    header("Location: trang_dang_nhap.php"); // Chuyển hướng nếu chưa đăng nhập
    exit();
}

$ma_suat_safe = mysqli_real_escape_string($conn, $ma_suat);
$ma_kh_safe = mysqli_real_escape_string($conn, $ma_khach_hang);
$ma_khuyen_mai = trim($_POST['MaKhuyenMai'] ?? '');

// 1. Lấy thông tin cơ bản: Phim, Giá vé và Số dư KH
$sql_info = "SELECT SC.GiaVeCoBan, P.TenPhim, KH.SoDuVND
             FROM suatchieu SC
             JOIN phim P ON SC.MaPhim = P.MaPhim
             LEFT JOIN KhachHang KH ON KH.MaKhachHang = '$ma_kh_safe'
             WHERE SC.MaSuatChieu = '$ma_suat_safe'";
$info = mysqli_fetch_assoc(mysqli_query($conn, $sql_info));
$gia_ve_co_ban = $info['GiaVeCoBan'] ?? 90000;
$so_du_tai_khoan = $info['SoDuVND'] ?? 0.00;
$ten_phim = $info['TenPhim'] ?? 'Phim';

// 2. Tính Tổng Tiền
$tong_tien_chua_giam = count($selected_seats) * $gia_ve_co_ban;
$gia_tri_giam = 0; // % giảm giá

// 3. Xử lý Khuyến Mãi
if (!empty($ma_khuyen_mai)) {
    $ma_km_safe = mysqli_real_escape_string($conn, $ma_khuyen_mai);
    $sql_km = "SELECT GiaTriGiam FROM khuyenmai WHERE MaKhuyenMai = '$ma_km_safe' AND NgayKetThuc >= NOW()";
    $result_km = mysqli_query($conn, $sql_km);
    
    if (mysqli_num_rows($result_km) > 0) {
        $km = mysqli_fetch_assoc($result_km);
        $gia_tri_giam = $km['GiaTriGiam']; 
    } else {
        $ma_khuyen_mai = ''; // Đặt rỗng nếu không hợp lệ
    }
}

// 4. Áp dụng giảm giá
$tong_tien_sau_giam = $tong_tien_chua_giam * (1 - $gia_tri_giam / 100);
?>

<!DOCTYPE html>
<html>
<head>
    <title>3. Thanh Toán - <?php echo $ten_phim; ?></title>
</head>
<body>
    <h1>💵 Xác Nhận Thanh Toán</h1>
    <h3>Phim: <?php echo htmlspecialchars($ten_phim); ?> | Ghế: <?php echo count($selected_seats); ?> (<?php echo implode(', ', array_map('htmlspecialchars', $selected_seats)); ?>)</h3>
    
    <hr>
    
    <form method="POST" action="thanh_toan.php">
        <input type="hidden" name="MaSuatChieu" value="<?php echo htmlspecialchars($ma_suat); ?>">
        <?php foreach ($selected_seats as $seat) { echo '<input type="hidden" name="selected_seats[]" value="' . htmlspecialchars($seat) . '">'; } ?>
        
        <label for="MaKhuyenMai">Mã Khuyến Mãi:</label>
        <input type="text" id="MaKhuyenMai" name="MaKhuyenMai" value="<?php echo htmlspecialchars($ma_khuyen_mai); ?>">
        <button type="submit">Áp Dụng</button>
        <?php if (!empty($_POST['MaKhuyenMai']) && $gia_tri_giam == 0): ?><span style="color: red;"> Mã không hợp lệ!</span><?php endif; ?>
    </form>
    
    <hr>
    
    <h2>Tổng Kết Thanh Toán</h2>
    <table border="1" cellpadding="10" cellspacing="0">
        <tr><td>**Tổng tiền chưa giảm**</td><td align="right"><?php echo number_format($tong_tien_chua_giam, 0, ',', '.'); ?> VND</td></tr>
        <tr><td>**Giảm giá (<?php echo $gia_tri_giam; ?>%)**</td><td align="right">-<?php echo number_format($tong_tien_chua_giam - $tong_tien_sau_giam, 0, ',', '.'); ?> VND</td></tr>
        <tr><td>**TỔNG CỘNG**</td><td align="right"><strong style="color: red; font-size: 1.2em;"><?php echo number_format($tong_tien_sau_giam, 0, ',', '.'); ?> VND</strong></td></tr>
        <tr><td>**Số dư tài khoản của bạn**</td><td align="right"><?php echo number_format($so_du_tai_khoan, 0, ',', '.'); ?> VND</td></tr>
    </table>

    <hr>
    
    <form method="POST" action="xu_ly_dat_ve.php">
        <input type="hidden" name="MaSuatChieu" value="<?php echo htmlspecialchars($ma_suat); ?>">
        <input type="hidden" name="TongTien" value="<?php echo $tong_tien_sau_giam; ?>">
        <input type="hidden" name="MaKhuyenMai" value="<?php echo htmlspecialchars($ma_khuyen_mai); ?>">
         <?php foreach ($selected_seats as $seat) { echo '<input type="hidden" name="selected_seats[]" value="' . htmlspecialchars($seat) . '">'; } ?>
        
        <label><input type="radio" name="PhuongThucThanhToan" value="TaiKhoan" required <?php echo ($so_du_tai_khoan < $tong_tien_sau_giam) ? 'disabled' : ''; ?>> Thanh toán bằng Số dư tài khoản</label><br>
        <label><input type="radio" name="PhuongThucThanhToan" value="ViDienTu" required> Thanh toán bằng Ví điện tử khác</label>
        
        <button type="submit" style="padding: 10px 20px; background: green; color: white;">XÁC NHẬN ĐẶT VÉ</button>
        <?php if ($so_du_tai_khoan < $tong_tien_sau_giam): ?>
        <p style="color: red;">*Vui lòng chọn phương thức thanh toán khác do Số dư tài khoản không đủ.</p>
        <?php endif; ?>
    </form>
</body>
</html>
<?php mysqli_close($conn); ?>