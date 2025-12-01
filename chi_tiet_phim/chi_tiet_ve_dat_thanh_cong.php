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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết vé đặt thành công</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffe5e5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border-top: 8px solid #d70000;
        }

        h2 {
            text-align: center;
            color: #d70000;
            font-size: 28px;
        }

        h3 {
            margin-top: 20px;
            color: #333;
        }

        ul {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }

        ul li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 16px;
        }

        ul li strong {
            color: #b30000;
        }

        .btn-home {
            display: block;
            text-align: center;
            margin-top: 25px;
            background: #d70000;
            color: #fff;
            padding: 12px 0;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.25s;
        }

        .btn-home:hover {
            background: #b30000;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>🎉 ĐẶT VÉ THÀNH CÔNG!</h2>

        <h3>Thông tin vé của bạn:</h3>
        <ul>
            <li><strong>Mã đặt vé:</strong> <?= htmlspecialchars($ve['MaDatVe']) ?></li>
            <li><strong>Khách hàng:</strong> <?= htmlspecialchars($ve['TenKhachHang']) ?></li>
            <li><strong>Phim:</strong> <?= htmlspecialchars($ve['TenPhim']) ?></li>
            <li><strong>Ngày chiếu:</strong> <?= htmlspecialchars($ve['NgayChieu']) ?></li>
            <li><strong>Giờ chiếu:</strong> <?= htmlspecialchars($ve['GioChieu']) ?></li>
            <li><strong>Ghế đã chọn:</strong> <?= htmlspecialchars($ten_ghe_str) ?></li>
            <li><strong>Số lượng vé:</strong> <?= htmlspecialchars($ve['SoLuong']) ?></li>
            <li><strong>Tổng tiền:</strong> <?= number_format($ve['TongTien'], 0, ',', '.') ?> VNĐ</li>
            <li><strong>Phương thức thanh toán:</strong> <?= htmlspecialchars($ve['PhuongThucThanhToan']) ?></li>
            <li><strong>Thời gian đặt:</strong> <?= htmlspecialchars($ve['ThoiGianDat']) ?></li>
        </ul>

        <a class="btn-home" href="../index.php">⬅ Về trang chủ</a>
    </div>
</body>
</html>
