<?php
session_start();
// Đã sửa lỗi đường dẫn: require "../Connection.php";
require "../Connection.php"; 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../Login&Register/Login.php");
    exit();
}

// ⭐ 1. Vấn đề bảo mật: Sử dụng mysqli_real_escape_string để làm sạch dữ liệu
$ma_khach_hang = mysqli_real_escape_string($conn, $_SESSION['user_id']);

$sql = "SELECT HoTen, Email, SoDu FROM khachhang WHERE MaKhachHang = '$ma_khach_hang'";
$result = mysqli_query($conn, $sql);

// ⭐ 2. Vấn đề Fatal Error: Kiểm tra kết quả truy vấn
if ($result === false) {
    // Nếu truy vấn thất bại, dừng lại và hiển thị lỗi để dễ dàng debug
    die("Lỗi truy vấn CSDL: " . mysqli_error($conn) . " | SQL: " . $sql);
}

// Nếu truy vấn thành công, tiếp tục fetch
$khach_hang = mysqli_fetch_assoc($result);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân</title>
    </head>
<body>
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
            <?php else: ?>
            <p style="color: red;">Không tìm thấy thông tin khách hàng với ID: <?php echo htmlspecialchars($ma_khach_hang); ?>. Vui lòng kiểm tra CSDL.</p>
            <?php endif; ?>
            <hr>
            <a href="../Login&Register/logout.php">Đăng xuất</a>
        </div>
    </div>
</body>
</html>