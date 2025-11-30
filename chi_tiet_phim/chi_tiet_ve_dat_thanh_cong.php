<?php
session_start();
require "../Connection.php";

// Lấy mã đặt vé từ URL
$ma_dat_ve = $_GET['MaDatVe'] ?? null;

if (!$ma_dat_ve) {
    die("Không tìm thấy mã đặt vé.");
}

// Truy vấn thông tin vé vừa đặt
$sql = "
    SELECT dv.*, kh.HoTen AS TenKhachHang, 
           DATE(sc.ThoiGianBatDau) AS NgayChieu, 
           TIME(sc.ThoiGianBatDau) AS GioChieu, 
           p.TenPhim
    FROM datve dv
    JOIN khachhang kh ON dv.MaKhachHang = kh.MaKhachHang
    JOIN suatchieu sc ON dv.MaSuatChieu = sc.MaSuatChieu
    JOIN phim p ON sc.MaPhim = p.MaPhim
    WHERE dv.MaDatVe = '" . mysqli_real_escape_string($conn, $ma_dat_ve) . "'
    LIMIT 1
";
$result = mysqli_query($conn, $sql);
$ve = mysqli_fetch_assoc($result);

if (!$ve) {
    die("Không tìm thấy thông tin vé.");
}

$ten_ghe_str = $ve['MaGheDaChon'];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Chi tiết vé đặt thành công</title>
    <meta charset="utf-8">
</head>

<body>
    <h2>ĐẶT VÉ THÀNH CÔNG!</h2>
    <h3>Thông tin vé của bạn:</h3>
    <ul>
        <li><strong>Mã đặt vé:</strong> <?php echo htmlspecialchars($ve['MaDatVe']); ?></li>
        <li><strong>Khách hàng:</strong> <?php echo htmlspecialchars($ve['TenKhachHang']); ?></li>
        <li><strong>Phim:</strong> <?php echo htmlspecialchars($ve['TenPhim']); ?></li>
        <li><strong>Ngày chiếu:</strong> <?php echo htmlspecialchars($ve['NgayChieu']); ?></li>
        <li><strong>Giờ chiếu:</strong> <?php echo htmlspecialchars($ve['GioChieu']); ?></li>
        <li><strong>Ghế đã chọn:</strong> <?php echo htmlspecialchars($ten_ghe_str); ?></li>
        <li><strong>Số lượng vé:</strong> <?php echo htmlspecialchars($ve['SoLuong']); ?></li>
        <li><strong>Tổng tiền:</strong> <?php echo number_format($ve['TongTien'], 0, ',', '.'); ?> VNĐ</li>
        <li><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($ve['PhuongThucThanhToan']); ?></li>
        <li><strong>Thời gian đặt:</strong> <?php echo htmlspecialchars($ve['ThoiGianDat']); ?></li>
    </ul>
    <a href="../index.php">Về trang chủ</a>
</body>

</html>