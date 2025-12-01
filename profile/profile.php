<?php
session_start();
require "../Connection.php"; 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../Login&Register/Login.php");
    exit();
}

// Lấy thông tin user
$ma_khach_hang = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$sql = "SELECT HoTen, Email, SoDu FROM khachhang WHERE MaKhachHang = '$ma_khach_hang'";
$result = mysqli_query($conn, $sql);
if ($result === false) {
    die("Lỗi truy vấn CSDL: " . mysqli_error($conn) . " | SQL: " . $sql);
}
$khach_hang = mysqli_fetch_assoc($result);

// Tính tổng chi tiêu trong tháng
$current_month = date('m');
$current_year = date('Y');

$sql_tongtien = "SELECT SUM(TongTien) AS tong_chi_tieu 
                 FROM datve 
                 WHERE MaKhachHang = '$ma_khach_hang' 
                 AND MONTH(ThoiGianDat) = '$current_month' 
                 AND YEAR(ThoiGianDat) = '$current_year'";
$result_tongtien = mysqli_query($conn, $sql_tongtien);

if ($result_tongtien === false) {
    die("Lỗi truy vấn tổng chi tiêu: " . mysqli_error($conn));
}

$tong_chi_tieu = mysqli_fetch_assoc($result_tongtien)['tong_chi_tieu'];
if (!$tong_chi_tieu) $tong_chi_tieu = 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân</title>
    <link rel="stylesheet" href="../styleproflie.css">
    <style>
        :root {
            --primary-red: #d11e3b;
            --dark-red: #a3182d;
        }

        .wrapper { 
            max-width: 800px; 
            width: 90%; 
            margin: 20px auto; 
            margin-top: 80px; 
        }
        
        .btn-back-fixed {
            position: fixed; 
            top: 20px; 
            left: 20px; 
            width: 45px;
            height: 45px;
            border-radius: 50%; /* Tạo hình tròn */
            background-color: var(--primary-red); 
            color: white;
            text-align: center;
            line-height: 45px; 
            text-decoration: none;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: background-color 0.3s, transform 0.2s;
            z-index: 1000;
        }

        .btn-back-fixed:hover {
            background-color: var(--dark-red);
            transform: scale(1.05);
        }
        .btn-back-fixed span {
            display: block;
            line-height: 45px; 
        }

        @media (max-width: 768px) {
            .btn-back-fixed {
                top: 15px; 
                left: 15px;
                width: 40px;
                height: 40px;
                line-height: 40px;
                font-size: 18px;
            }
        }
        
        .btn-logout {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
            background-color: var(--primary-red);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-logout:hover {
            background-color: var(--dark-red);
        }
    </style>
</head>
<body>
    
    <a href="../index.php" class="btn-back-fixed" title="Quay lại Trang Chủ">
        <span>&larr;</span>
    </a>

    <div class="wrapper">
        <div class="main">
            <h2>👤 Thông tin Tài khoản Cá nhân</h2>
            <p>Đây là trang profile chứng minh bạn đã đăng nhập thành công!</p>
            
            <?php if ($khach_hang): ?>
            <hr>
            <h3>Xin chào, <?php echo htmlspecialchars($khach_hang['HoTen']); ?></h3>
            <p><strong>Mã Khách Hàng:</strong> <?php echo htmlspecialchars($ma_khach_hang); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($khach_hang['Email']); ?></p>
            <p><strong>Số dư:</strong> <?php echo number_format($khach_hang['SoDu'], 0, ',', '.'); ?> VND</p>
            <p><strong>Tổng chi tiêu trong tháng:</strong> <?php echo number_format($tong_chi_tieu, 0, ',', '.'); ?> VND</p>
            <?php else: ?>
            <p style="color: red;">Không tìm thấy thông tin khách hàng với ID: <?php echo htmlspecialchars($ma_khach_hang); ?>.</p>
            <?php endif; ?>

            <hr>
            <a href="../chi_tiet_phim/lich_su_dat_ve.php" class="btn-logout">Xem Lịch Sử</a>
            <a href="../Login&Register/logout.php" class="btn-logout">Đăng xuất</a>
        </div>
    </div>
</body>
</html>