<?php
session_start();
require "../Connection.php";

$ma_khach_hang = $_SESSION['user_id'] ?? null;
if (!$ma_khach_hang) {
    header("Location: ../Login&Register/Login.php");
    exit("Vui lòng đăng nhập để xem lịch sử hoàn vé.");
}

// Lấy danh sách hoàn vé của tài khoản
$sql = "
    SELECT ht.*, dv.MaGheDaChon, dv.TongTien, dv.ThoiGianDat, dv.PhuongThucThanhToan, dv.MaDatVe, 
            rr.PhanTramHoan, rr.MoTa AS MoTaQuyTac
    FROM hoantien ht
    JOIN datve dv ON ht.MaDatVe = dv.MaDatVe
    LEFT JOIN refundrule rr ON ht.MaQuyTac = rr.MaQuyTac
    WHERE dv.MaKhachHang = '" . mysqli_real_escape_string($conn, $ma_khach_hang) . "'
    ORDER BY ht.NgayHoanTien DESC
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Lịch sử hoàn vé</title>
    <style>
        :root {
            --primary-red: #cc0000;
            --dark-red: #a30000;
            --light-bg: #f8f9fa;
            --dark-text: #333;
            --header-bg: #f5f5f5;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: var(--primary-red);
            border-bottom: 3px solid var(--primary-red);
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        /* Bảng */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: var(--primary-red); /* Màu đỏ cho tiêu đề cột */
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2; /* Màu xen kẽ */
        }

        tr:hover {
            background-color: #fdd;
        }
        
        /* Cột trạng thái */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85em;
        }

        .status-pending {
            background-color: #ffc107; /* Vàng cam */
            color: var(--dark-text);
        }

        .status-success {
            background-color: #28a745; /* Xanh lá cây */
            color: white;
        }

        /* Nút xem chi tiết */
        td a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s;
        }
        
        td a:hover {
            color: var(--dark-red);
            text-decoration: underline;
        }

        /* Quay lại lịch sử vé */
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: var(--dark-red);
            text-decoration: none;
            font-weight: bold;
            padding: 10px 0;
            border: 1px solid var(--dark-red);
            border-radius: 5px;
            max-width: 300px;
            margin: 20px auto;
            transition: background-color 0.2s, color 0.2s;
        }
        .back-link:hover {
            background-color: var(--dark-red);
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>LỊCH SỬ HOÀN VÉ</h2>
        <table>
            <tr>
                <th>Mã HT</th>
                <th>Mã Đặt Vé</th>
                <th>Ghế</th>
                <th>Tổng tiền vé</th>
                <th>% Hoàn</th>
                <th>Số tiền hoàn</th>
                <th>Thời gian yêu cầu</th>
                <th>Trạng thái</th>
                <th>Chi tiết</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['MaHoanTien']); ?></td>
                    <td><?php echo htmlspecialchars($row['MaDatVe']); ?></td>
                    <td>
                        <?php
                        // Lấy danh sách MaGhe từ MaGheDaChon
                        $ma_ghe_arr = array_map('trim', explode(',', $row['MaGheDaChon']));
                        $ten_ghe_arr = [];
                        if (!empty($ma_ghe_arr)) {
                            // Tạo mảng tham chiếu cho các tham số mysqli_real_escape_string
                            $params = array_merge([$conn], $ma_ghe_arr);
                            
                            // Sử dụng array_map với callable và tham số động (cần PHP >= 5.6)
                            // Hoặc đơn giản là lặp qua mảng như dưới đây cho khả năng tương thích cao hơn.
                            $escaped_ma_ghe_arr = array_map(function($g) use ($conn) {
                                return mysqli_real_escape_string($conn, $g);
                            }, $ma_ghe_arr);
                            
                            $ma_ghe_in = implode("','", $escaped_ma_ghe_arr);
                            
                            $sql_ghe = "SELECT SoGhe FROM ghe WHERE MaGhe IN ('$ma_ghe_in')";
                            $result_ghe = mysqli_query($conn, $sql_ghe);
                            
                            if ($result_ghe) {
                                while ($ghe = mysqli_fetch_assoc($result_ghe)) {
                                    $ten_ghe_arr[] = $ghe['SoGhe'];
                                }
                            }
                        }
                        echo htmlspecialchars(implode(', ', $ten_ghe_arr));
                        ?>
                    </td>
                    <td><?php echo number_format($row['TongTien'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo $row['PhanTramHoan'] ?? '-'; ?>%</td>
                    <td><?php echo number_format($row['SoTienHoan'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo htmlspecialchars($row['NgayHoanTien']); ?></td>
                    <td>
                        <?php
                        $status = htmlspecialchars($row['TrangThaiHoan']);
                        if ($status == 'Đang chờ xử lý') {
                            echo "<span class='status-badge status-pending'>$status</span>";
                        } elseif ($status == 'Hoàn Tiền Thành Công') {
                            echo "<span class='status-badge status-success'>$status</span>";
                        } else {
                            echo "<span class='status-badge'>$status</span>";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="chitiet_hoantien.php?MaHoanTien=<?php echo urlencode($row['MaHoanTien']); ?>">Xem</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <a href="../chi_tiet_phim/lich_su_dat_ve.php" class="back-link">Quay lại lịch sử đặt vé</a>
    </div>
</body>

</html>
<?php mysqli_close($conn); ?>