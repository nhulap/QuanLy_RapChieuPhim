<?php
session_start();
require "../Connection.php";

$ma_hoan_tien = $_GET['MaHoanTien'] ?? null;
if (!$ma_hoan_tien) {
    die("Không tìm thấy mã hoàn tiền.");
}

// Truy vấn thông tin hoàn tiền và vé liên quan
$sql = "
    SELECT ht.*, dv.MaGheDaChon, dv.TongTien, dv.ThoiGianDat, dv.PhuongThucThanhToan, dv.MaDatVe, 
            rr.PhanTramHoan, rr.MoTa AS MoTaQuyTac
    FROM hoantien ht
    JOIN datve dv ON ht.MaDatVe = dv.MaDatVe
    LEFT JOIN refundrule rr ON ht.MaQuyTac = rr.MaQuyTac
    WHERE ht.MaHoanTien = '" . mysqli_real_escape_string($conn, $ma_hoan_tien) . "'
    LIMIT 1
";
$result = mysqli_query($conn, $sql);
$hoan = mysqli_fetch_assoc($result);

if (!$hoan) {
    die("Không tìm thấy thông tin hoàn tiền.");
}

// Lấy danh sách tên ghế (cần thiết nếu muốn hiển thị tên ghế thay vì mã ghế)
$ten_ghe_list = '';
$ma_ghe_arr = array_map('trim', explode(',', $hoan['MaGheDaChon']));
if (!empty($ma_ghe_arr)) {
    $escaped_ma_ghe_arr = array_map(function($g) use ($conn) {
        return mysqli_real_escape_string($conn, $g);
    }, $ma_ghe_arr);
    
    $ma_ghe_in = implode("','", $escaped_ma_ghe_arr);
    $sql_ghe = "SELECT SoGhe FROM ghe WHERE MaGhe IN ('$ma_ghe_in')";
    $result_ghe = mysqli_query($conn, $sql_ghe);
    
    if ($result_ghe) {
        $ten_ghe_arr = [];
        while ($ghe = mysqli_fetch_assoc($result_ghe)) {
            $ten_ghe_arr[] = $ghe['SoGhe'];
        }
        $ten_ghe_list = htmlspecialchars(implode(', ', $ten_ghe_arr));
    }
}


// Xác định trạng thái và style
$trang_thai = $hoan['TrangThaiHoan'];
$msg = '';
if ($trang_thai == 'Đang chờ xử lý') {
    $msg = "<span class='status-badge status-pending'>Đang chờ xử lý</span>";
} elseif ($trang_thai == 'Hoàn Tiền Thành Công') {
    $msg = "<span class='status-badge status-success'>Hoàn Tiền Thành Công</span>";
} else {
    $msg = "<span class='status-badge status-default'>" . htmlspecialchars($trang_thai) . "</span>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Chi tiết hoàn tiền vé</title>
    <style>
        :root {
            --primary-red: #cc0000;
            --dark-red: #a30000;
            --light-bg: #f8f9fa;
            --dark-text: #333;
            --border-color: #ddd;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: var(--primary-red);
            border-bottom: 3px solid var(--primary-red);
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        ul {
            list-style: none;
            padding: 0;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin-bottom: 30px;
        }

        ul li {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
        }

        ul li:last-child {
            border-bottom: none;
        }

        ul li strong {
            color: var(--dark-red);
            width: 40%; /* Cố định độ rộng cho nhãn */
        }
        
        /* Hiển thị số tiền hoàn to và rõ ràng */
        .highlight-amount {
            font-size: 1.2em;
            color: var(--primary-red);
            font-weight: bold;
        }

        /* Badge trạng thái */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .status-pending {
            background-color: #ffc107; /* Vàng cam */
            color: var(--dark-text);
        }

        .status-success {
            background-color: #28a745; /* Xanh lá cây */
            color: white;
        }
        
        .status-default {
            background-color: #6c757d;
            color: white;
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
        <h2>CHI TIẾT HOÀN TIỀN VÉ</h2>
        <ul>
            <li><strong>Mã hoàn tiền:</strong> <span><?php echo htmlspecialchars($hoan['MaHoanTien']); ?></span></li>
            <li><strong>Mã đặt vé:</strong> <span><?php echo htmlspecialchars($hoan['MaDatVe']); ?></span></li>
            <li><strong>Ghế đã đặt:</strong> <span><?php echo $ten_ghe_list ?: 'N/A'; ?></span></li>
            <li><strong>Tổng tiền vé:</strong> <span><?php echo number_format($hoan['TongTien'], 0, ',', '.'); ?> VNĐ</span></li>
            <li><strong>Phương thức TT:</strong> <span><?php echo htmlspecialchars($hoan['PhuongThucThanhToan']); ?></span></li>
            <li><strong>Thời gian đặt vé:</strong> <span><?php echo htmlspecialchars($hoan['ThoiGianDat']); ?></span></li>
            <li><strong>Thời gian yêu cầu hoàn:</strong> <span><?php echo htmlspecialchars($hoan['NgayHoanTien']); ?></span></li>
            <li><strong>Lý do hoàn:</strong> <span><?php echo htmlspecialchars($hoan['LyDoHoan']); ?></span></li>
            <li><strong>Quy tắc áp dụng:</strong> <span><?php echo htmlspecialchars($hoan['MoTaQuyTac']); ?> (<?php echo $hoan['PhanTramHoan']; ?>%)</span></li>
            <li><strong>Số tiền hoàn:</strong> <span class="highlight-amount"><?php echo number_format($hoan['SoTienHoan'], 0, ',', '.'); ?> VNĐ</span></li>
            <li><strong>Trạng thái hoàn tiền:</strong> <span><?php echo $msg; ?></span></li>
        </ul>
        <a href="../chi_tiet_phim/lich_su_dat_ve.php" class="back-link">Quay lại lịch sử đặt vé</a>
    </div>
</body>

</html>
<?php mysqli_close($conn); ?>