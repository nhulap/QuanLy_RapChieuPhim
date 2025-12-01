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
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch sử đặt vé</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffe5e5;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            max-width: 1100px;
            margin: 30px auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-top: 8px solid #d70000;
        }

        h2 {
            color: #d70000;
            text-align: center;
            margin-bottom: 25px;
            font-size: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 10px;
        }

        th {
            background: #d70000;
            color: white;
            padding: 12px;
            font-size: 16px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 15px;
        }

        tr:nth-child(even) {
            background: #fff7f7;
        }

        tr:hover {
            background: #ffe1e1;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            background: #d70000;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.25s;
        }

        .back-btn:hover {
            background: #b30000;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>🎬 LỊCH SỬ ĐẶT VÉ</h2>

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
                <th>Thanh toán</th>
                <th>Thời gian đặt</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['MaDatVe']) ?></td>
                    <td><?= htmlspecialchars($row['TenPhim']) ?></td>
                    <td><?= htmlspecialchars($row['TenRap']) ?></td>
                    <td><?= htmlspecialchars($row['TenPhong']) ?></td>
                    <td><?= htmlspecialchars($row['NgayChieu']) ?></td>
                    <td><?= htmlspecialchars($row['GioChieu']) ?></td>
                    <td><?= htmlspecialchars($row['MaGheDaChon']) ?></td>
                    <td><?= htmlspecialchars($row['SoLuong']) ?></td>
                    <td><?= number_format($row['TongTien'], 0, ',', '.') ?> VNĐ</td>
                    <td><?= htmlspecialchars($row['TrangThaiThanhToan']) ?></td>
                    <td><?= htmlspecialchars($row['ThoiGianDat']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <a class="back-btn" href="../index.php">⬅ Về trang chủ</a>
    </div>

</body>
</html>
