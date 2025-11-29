<?php
session_start();

require "../Connection.php";

$ma_suat = $_POST['MaSuatChieu'] ?? die("Thiếu Mã Suất Chiếu.");
$selected_seats = $_POST['selected_seats'] ?? [];

if (empty($selected_seats)) {
    // Quay lại trang chọn ghế nếu chưa chọn ghế
    header("Location: chon_ghe.php?MaSuatChieu=" . urlencode($ma_suat));
    exit();
}

// =======================================================
// ⭐ BỎ PHẦN GÁN CỨNG - LẤY TỪ SESSION SAU KHI ĐĂNG NHẬP
// =======================================================
$ma_khach_hang = $_SESSION['user_id'] ?? null;

if (!$ma_khach_hang) {
    // Nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] . '?' . http_build_query($_POST);
    header("Location: ../Login&Register/Login.php");
    exit("Vui lòng đăng nhập để tiếp tục đặt vé.");
}
// =======================================================


$ma_suat_safe = mysqli_real_escape_string($conn, $ma_suat);
$ma_kh_safe = mysqli_real_escape_string($conn, $ma_khach_hang);
$ma_khuyen_mai = trim($_POST['MaKhuyenMai'] ?? '');

// 1. Lấy thông tin cơ bản: Phim, Giá vé và SỐ DƯ KHÁCH HÀNG
// Chú ý: LEFT JOIN là cần thiết để đảm bảo thông tin suất chiếu luôn được lấy, 
// ngay cả khi không tìm thấy SoDu (dù logic này đã được kiểm tra ở bước trước)
$sql_info = "SELECT SC.GiaVeCoBan, P.TenPhim, KH.SoDu
             FROM suatchieu SC
             JOIN phim P ON SC.MaPhim = P.MaPhim
             LEFT JOIN khachhang KH ON KH.MaKhachHang = '$ma_kh_safe'
             WHERE SC.MaSuatChieu = '$ma_suat_safe'";
             
$result_info = mysqli_query($conn, $sql_info);
if ($result_info === false) {
    die("Lỗi truy vấn thông tin cơ bản: " . mysqli_error($conn));
}

$info = mysqli_fetch_assoc($result_info);
if (!$info) {
    die("Lỗi: Không tìm thấy thông tin suất chiếu.");
}

$gia_ve_co_ban = $info['GiaVeCoBan'] ?? 90000;
$so_du_tai_khoan = $info['SoDu'] ?? 0.00; // Lấy cột SoDu
$ten_phim = $info['TenPhim'] ?? 'Phim';


// 2. Tính Tổng Tiền
$tong_tien_chua_giam = count($selected_seats) * $gia_ve_co_ban;
$gia_tri_giam = 0; // % giảm giá

// 3. Xử lý Khuyến Mãi
if (!empty($ma_khuyen_mai)) {
    $ma_km_safe = mysqli_real_escape_string($conn, $ma_khuyen_mai);
    // Kiểm tra Ngày kết thúc VÀ đảm bảo Mã Khuyến mãi tồn tại
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
$tong_tien_sau_giam = round($tong_tien_sau_giam, 0); // Làm tròn số tiền cuối cùng

?>

<!DOCTYPE html>
<html>
<head>
    <title>3. Thanh Toán - <?php echo $ten_phim; ?></title>
    <link rel="stylesheet" href="../stylelap.css">
    <style>
        /* CSS Tùy chỉnh cho trang thanh toán */
        .payment-summary {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .payment-summary table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .payment-summary table td {
            padding: 10px;
            border: 1px solid #eee;
        }
        .payment-methods label {
            display: block;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .payment-methods label:hover {
            background-color: #f1f1f1;
        }
        .payment-methods input[type="radio"]:disabled + span {
            color: #888;
        }
        .payment-methods input[type="radio"]:disabled {
            cursor: not-allowed;
        }
        #btn-confirm {
            width: 100%;
            padding: 15px 20px;
            background: #d11e3b; /* Màu CGV */
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        #btn-confirm:hover {
            background: #a3182d;
        }
        .promo-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .promo-form input[type="text"] {
            padding: 10px;
            flex-grow: 1;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .promo-form button {
            padding: 10px 20px;
            background: #5cb85c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header"><div class="logo">CGV CINEMAS</div></div>
        <div class="menu">
            <ul>
                <li><a href="../index.php">Trang chủ</a></li>
                </ul>
        </div>
        
        <div class="main">
            <h1>💵 Xác Nhận Thanh Toán</h1>
            
            <div class="payment-summary">
                <h3>Phim: <?php echo htmlspecialchars($ten_phim); ?></h3>
                <p>Ghế đã chọn: **<?php echo count($selected_seats); ?>** (<?php echo implode(', ', array_map('htmlspecialchars', $selected_seats)); ?>)</p>
                
                <hr>
                
                <h2>Áp Dụng Khuyến Mãi</h2>
                <form method="POST" action="thanh_toan.php" class="promo-form">
                    <input type="hidden" name="MaSuatChieu" value="<?php echo htmlspecialchars($ma_suat); ?>">
                    <?php foreach ($selected_seats as $seat) { echo '<input type="hidden" name="selected_seats[]" value="' . htmlspecialchars($seat) . '">'; } ?>
                    
                    <input type="text" id="MaKhuyenMai" name="MaKhuyenMai" placeholder="Nhập mã khuyến mãi" value="<?php echo htmlspecialchars($_POST['MaKhuyenMai'] ?? $ma_khuyen_mai); ?>">
                    <button type="submit">Áp Dụng</button>
                    <?php if (!empty($_POST['MaKhuyenMai']) && $gia_tri_giam == 0): ?><span style="color: red; margin-left: 10px;"> Mã không hợp lệ!</span><?php endif; ?>
                </form>
                
                <hr>
                
                <h2>Tổng Kết</h2>
                <table>
                    <tr><td>**Tổng tiền chưa giảm**</td><td align="right"><?php echo number_format($tong_tien_chua_giam, 0, ',', '.'); ?> VND</td></tr>
                    <tr><td>**Giảm giá (<?php echo $gia_tri_giam; ?>%)**</td><td align="right"><span style="color: green;">-<?php echo number_format($tong_tien_chua_giam - $tong_tien_sau_giam, 0, ',', '.'); ?> VND</span></td></tr>
                    <tr><td>**TỔNG CỘNG**</td><td align="right"><strong style="color: #d11e3b; font-size: 1.2em;"><?php echo number_format($tong_tien_sau_giam, 0, ',', '.'); ?> VND</strong></td></tr>
                    <tr><td>**Số dư tài khoản của bạn**</td><td align="right"><?php echo number_format($so_du_tai_khoan, 0, ',', '.'); ?> VND</td></tr>
                </table>

                <hr>
                
                <form method="POST" action="xu_ly_dat_ve.php" class="payment-methods">
                    <input type="hidden" name="MaSuatChieu" value="<?php echo htmlspecialchars($ma_suat); ?>">
                    <input type="hidden" name="TongTien" value="<?php echo $tong_tien_sau_giam; ?>">
                    <input type="hidden" name="MaKhuyenMai" value="<?php echo htmlspecialchars($ma_khuyen_mai); ?>">
                    <?php foreach ($selected_seats as $seat) { echo '<input type="hidden" name="selected_seats[]" value="' . htmlspecialchars($seat) . '">'; } ?>
                    
                    <h2>Chọn Phương Thức Thanh Toán</h2>
                    
                    <label>
                        <input type="radio" name="PhuongThucThanhToan" value="TaiKhoan" required <?php echo ($so_du_tai_khoan < $tong_tien_sau_giam) ? 'disabled' : ''; ?>>
                        <span>Thanh toán bằng **Số dư tài khoản**</span> <?php if ($so_du_tai_khoan < $tong_tien_sau_giam): ?>
                            <span style="color: red;">* (Số dư không đủ)</span>
                        <?php endif; ?>
                    </label>
                    
                    <label>
                        <input type="radio" name="PhuongThucThanhToan" value="ViDienTu" required <?php echo ($so_du_tai_khoan < $tong_tien_sau_giam) ? 'checked' : ''; ?>>
                        <span>Thanh toán bằng **Ví điện tử/Thẻ Quốc Tế**</span>
                    </label>
                    
                    <button type="submit" id="btn-confirm">XÁC NHẬN ĐẶT VÉ</button>
                    
                    <?php if ($so_du_tai_khoan < $tong_tien_sau_giam): ?>
                    <p style="color: red; text-align: center; margin-top: 15px;">*Vui lòng chọn phương thức thanh toán khác do Số dư tài khoản không đủ.</p>
                    <?php endif; ?>
                </form>
            </div>
            
        </div>
        
    </div>

</body>
</html>

<?php mysqli_close($conn); ?>