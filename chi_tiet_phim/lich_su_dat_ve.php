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
                <th>Hoàn Vé</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['MaDatVe']) ?></td>
                    <td><?= htmlspecialchars($row['TenPhim']) ?></td>
                    <td><?= htmlspecialchars($row['TenRap']) ?></td>
                    <td><?= htmlspecialchars($row['TenPhong']) ?></td>
                    <td><?= htmlspecialchars($row['NgayChieu']) ?></td>
                    <td><?= htmlspecialchars($row['GioChieu']) ?></td>
                    <td>
                    <?php
                    $ma_ghe_arr = array_map('trim', explode(',', $row['MaGheDaChon']));
                    $ten_ghe_arr = [];
                    if (!empty($ma_ghe_arr)) {
                        // Chuẩn bị danh sách mã ghế hợp lệ (chỉ số, không rỗng)
                        $ma_ghe_valid = array_filter($ma_ghe_arr, function ($v) {
                            return $v !== '';
                        });
                        if (!empty($ma_ghe_valid)) {
                            $ma_ghe_in = implode("','", array_map('mysqli_real_escape_string', array_fill(0, count($ma_ghe_valid), $conn), $ma_ghe_valid));
                            $sql_ghe = "SELECT MaGhe, SoGhe FROM ghe WHERE MaGhe IN ('$ma_ghe_in')";
                            $result_ghe = mysqli_query($conn, $sql_ghe);
                            $map_ghe = [];
                            while ($ghe = mysqli_fetch_assoc($result_ghe)) {
                                $map_ghe[$ghe['MaGhe']] = $ghe['SoGhe'];
                            }
                            // Hiển thị đúng thứ tự, nếu không tìm thấy thì hiển thị lại mã ghế
                            foreach ($ma_ghe_arr as $mg) {
                                $ten_ghe_arr[] = $map_ghe[$mg] ?? $mg;
                            }
                        } else {
                            $ten_ghe_arr = $ma_ghe_arr;
                        }
                    }
                    echo htmlspecialchars(implode(', ', $ten_ghe_arr));
                    ?>
                </td>
                    <td><?= htmlspecialchars($row['SoLuong']) ?></td>
                    <td><?= number_format($row['TongTien'], 0, ',', '.') ?> VNĐ</td>
                    <td><?= htmlspecialchars($row['TrangThaiThanhToan']) ?></td>
                    <td><?= htmlspecialchars($row['ThoiGianDat']) ?></td>
                    <td>
                    <?php
                    // Kiểm tra vé đã có yêu cầu hoàn tiền chưa
                    $ma_dat_ve = mysqli_real_escape_string($conn, $row['MaDatVe']);
                    $sql_check_hoan = "SELECT 1 FROM hoantien WHERE MaDatVe = '$ma_dat_ve' LIMIT 1";
                    $result_check_hoan = mysqli_query($conn, $sql_check_hoan);
                    $da_hoan = mysqli_num_rows($result_check_hoan) > 0;
                    ?>
                   <?php if ($row['TrangThaiThanhToan'] === 'Thanh Toán Thành Công' && !$da_hoan): ?>
    <a href="../refundrule/hoantien.php?MaDatVe=<?php echo urlencode($row['MaDatVe']); ?>" 
       onclick="return confirm('Bạn chắc chắn muốn hoàn vé này?');"
       style="
           display: inline-block;
           padding: 8px 15px;
           margin: 5px;
           border-radius: 5px;
           text-decoration: none;
           font-weight: bold;
           cursor: pointer;
           background-color: #cc0000;
           color: white; 
           border: 1px solid #a30000;
       ">
       Hoàn vé
    </a>
<?php endif; ?>
                </td>

                </tr>
            <?php endwhile; ?>
        </table>

        <a class="back-btn" href="../index.php">⬅ Về trang chủ</a>
         <a class="back-btn" href="../refundrule/lichsu_hoanve.php"> Xem Lịch Sử Hoàn Vé</a>
    </div>

</body>
</html>
