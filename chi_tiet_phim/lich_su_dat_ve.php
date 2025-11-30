<?php
session_start();
require "../Connection.php";

// Kiểm tra đăng nhập
$ma_khach_hang = $_SESSION['user_id'] ?? null;
if (!$ma_khach_hang) {
    header("Location: ../Login&Register/Login.php");
    exit("Vui lòng đăng nhập để xem lịch sử đặt vé.");
}

// Lấy danh sách vé đã đặt của tài khoản
$sql = "
    SELECT dv.*, 
           DATE(sc.ThoiGianBatDau) AS NgayChieu, 
           TIME(sc.ThoiGianBatDau) AS GioChieu, 
           p.TenPhim, 
           pc.TenPhong, 
           rc.TenRap
    FROM datve dv
    JOIN suatchieu sc ON dv.MaSuatChieu = sc.MaSuatChieu
    JOIN phim p ON sc.MaPhim = p.MaPhim
    JOIN phongchieu pc ON sc.MaPhong = pc.MaPhong
    JOIN rapchieu rc ON pc.MaRap = rc.MaRap
    WHERE dv.MaKhachHang = '" . mysqli_real_escape_string($conn, $ma_khach_hang) . "'
    ORDER BY dv.ThoiGianDat DESC
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Lịch sử đặt vé</title>
    <meta charset="utf-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        th {
            background: #eee;
        }
    </style>
</head>

<body>
    <h2>LỊCH SỬ ĐẶT VÉ</h2>
    <table>
        <tr>
            <th>Mã vé</th>
            <th>Phim</th>
            <th>Rạp</th>
            <th>Phòng</th>
            <th>Ngày chiếu</th>
            <th>Giờ chiếu</th>
            <th>Ghế</th>
            <th>Số lượng</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Thời gian đặt</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['MaDatVe']); ?></td>
                <td><?php echo htmlspecialchars($row['TenPhim']); ?></td>
                <td><?php echo htmlspecialchars($row['TenRap']); ?></td>
                <td><?php echo htmlspecialchars($row['TenPhong']); ?></td>
                <td><?php echo htmlspecialchars($row['NgayChieu']); ?></td>
                <td><?php echo htmlspecialchars($row['GioChieu']); ?></td>
                <td><?php echo htmlspecialchars($row['MaGheDaChon']); ?></td>
                <td><?php echo htmlspecialchars($row['SoLuong']); ?></td>
                <td><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> VNĐ</td>
                <td><?php echo htmlspecialchars($row['TrangThaiThanhToan']); ?></td>
                <td><?php echo htmlspecialchars($row['ThoiGianDat']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="../index.php">Về trang chủ</a>
</body>

</html>