<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh'); 

// ⭐ 1. KIỂM TRA ĐĂNG NHẬP ⭐
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
    header("Location: ../Login&Register/Login.php");
    exit;
}
$ma_khach_hang = $_SESSION['user_id'];
require "../Connection.php"; 
require_once __DIR__ . '/../config/config.php'; 
// ⭐ 2. KHỞI TẠO BIẾN TỪ FORM HOẶC GIÁ TRỊ MẶC ĐỊNH ⭐
$selected_rap = $_POST['MaRap'] ?? ''; 
$selected_phong = $_POST['MaPhong'] ?? '';
$bat_dau = $_POST['ThoiGianBatDau'] ?? '';
$ket_thuc = $_POST['ThoiGianKetThuc'] ?? '';
$selected_phim = $_POST['MaPhim'] ?? 'none';
$muc_dich = $_POST['MucDichThue'] ?? 'Tiệc sinh nhật';

// ⭐ 3. TRUY VẤN DỮ LIỆU CƠ BẢN ⭐
// Rạp Chiếu
$sql_rap = "SELECT MaRap, TenRap FROM rapchieu ORDER BY TenRap";
$result_rap = mysqli_query($conn, $sql_rap);

// Phòng VIP (Lấy tất cả để PHP lọc)
$sql_phong = "SELECT MaPhong, MaRap, TenPhong FROM phongchieu WHERE LoaiPhong = 'VIP' ORDER BY MaRap, TenPhong";
$result_phong = mysqli_query($conn, $sql_phong);
$phong_vip_data = mysqli_fetch_all($result_phong, MYSQLI_ASSOC); // Lấy tất cả vào mảng

// Phim đang chiếu
$sql_phim = "SELECT MaPhim, TenPhim FROM phim WHERE NgayKhoiChieu <= CURDATE() ORDER BY TenPhim"; 
$result_phim = mysqli_query($conn, $sql_phim);

// ⭐ 4. ĐỊNH NGHĨA VÀ TÍNH TOÁN GIÁ BẰNG PHP ⭐
$GIA_CO_BAN_MOI_GIO = 500000; 
$PHU_PHI_PHIM = 100000; 

$tong_tien_hien_thi = "0 VND";
$tong_tien_value = 0;
$thong_bao_loi = '';
$is_valid = false;

if (!empty($bat_dau) && !empty($ket_thuc)) {
    $start_ts = strtotime($bat_dau);
    $end_ts = strtotime($ket_thuc);
    $now_ts = time(); 

    if ($start_ts < $now_ts) {
        $thong_bao_loi = "LỖI: Thời gian bắt đầu phải ở tương lai.";
    } elseif ($start_ts >= $end_ts) {
        $thong_bao_loi = "LỖI: Thời gian kết thúc phải sau thời gian bắt đầu.";
    } else {
        $duration_hours = ($end_ts - $start_ts) / 3600;

        if ($duration_hours > 3.01) { 
            $thong_bao_loi = "LỖI: Thời gian thuê tối đa là 3 giờ.";
        } else {
            // Tính tiền thuê: làm tròn lên theo giờ (ceil)
            $so_gio_tinh_tien = ceil($duration_hours);
            $tong_tien_value = $so_gio_tinh_tien * $GIA_CO_BAN_MOI_GIO;

            // Cộng phụ phí phim
            if ($selected_phim !== 'none') {
                $tong_tien_value += $PHU_PHI_PHIM;
            }

            $tong_tien_hien_thi = number_format($tong_tien_value, 0, ',', '.') . ' VND';
            $is_valid = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../stylelap.css"> 
    <title>Đặt Thuê Phòng VIP Riêng (PHP Thuần)</title>
</head>
<body>
    <?php include '../layout/header.php'; ?>
    <div class="main">
        <h2>📅 Đặt Thuê Phòng VIP Riêng</h2>
        <form id="datphong-form" action="" method="POST"> 
            
            <input type="hidden" name="MaKhachHang" value="<?php echo htmlspecialchars($ma_khach_hang); ?>">

            <label for="MaRap">Chọn Rạp Chiếu:</label>
            <select name="MaRap" id="MaRap" required onchange="this.form.submit()"> 
                <option value="">-- Chọn Rạp --</option>
                <?php while ($row = mysqli_fetch_assoc($result_rap)): ?>
                    <option value="<?php echo $row['MaRap']; ?>" 
                            <?php echo ($row['MaRap'] == $selected_rap) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['TenRap']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <label for="MaPhong">Chọn Phòng VIP:</label>
            <select name="MaPhong" id="MaPhong" required onchange="this.form.submit()">
                <option value="">-- Chọn Phòng --</option>
                <?php foreach ($phong_vip_data as $row): ?>
                    <?php 
                    // ⭐ Logic lọc phòng bằng PHP ⭐
                    $can_display = empty($selected_rap) || ($row['MaRap'] == $selected_rap);
                    $selected = ($row['MaPhong'] == $selected_phong) ? 'selected' : '';
                    
                    // Nếu không thuộc rạp đang chọn, hiển thị option nhưng disabled
                    $disabled = ($row['MaRap'] != $selected_rap && !empty($selected_rap)) ? 'disabled' : ''; 
                    $style = ($row['MaRap'] != $selected_rap && !empty($selected_rap)) ? 'style="display:none;"' : '';
                    ?>
                    <option class="phong-option" 
                            value="<?php echo $row['MaPhong']; ?>" 
                            data-rap="<?php echo $row['MaRap']; ?>"
                            <?php echo $selected; ?>
                            <?php echo $disabled; ?>
                            <?php echo $style; ?>>
                        <?php echo htmlspecialchars($row['TenPhong']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="ThoiGianBatDau">Thời Gian Bắt Đầu:</label>
            <input type="datetime-local" id="ThoiGianBatDau" name="ThoiGianBatDau" required 
                   value="<?php echo htmlspecialchars($bat_dau); ?>" 
                   min="<?php echo date('Y-m-d\TH:i'); ?>"
                   onchange="this.form.submit()">

            <label for="ThoiGianKetThuc">Thời Gian Kết Thúc (Tối đa 3 giờ):</label>
            <input type="datetime-local" id="ThoiGianKetThuc" name="ThoiGianKetThuc" required 
                   value="<?php echo htmlspecialchars($ket_thuc); ?>"
                   onchange="this.form.submit()">
            
            <label for="MaPhim">Chọn Phim (Tùy chọn cho sự kiện/chiếu riêng):</label>
            <select name="MaPhim" id="MaPhim" onchange="this.form.submit()">
                <option value="none" <?php echo ($selected_phim == 'none') ? 'selected' : ''; ?>>-- Không Chiếu Phim (Sự kiện khác) --</option>
                <?php while ($row = mysqli_fetch_assoc($result_phim)): ?>
                    <option value="<?php echo $row['MaPhim']; ?>" <?php echo ($row['MaPhim'] == $selected_phim) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['TenPhim']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="MucDichThue">Mục Đích Thuê:</label>
            <input type="text" id="MucDichThue" name="MucDichThue" value="<?php echo htmlspecialchars($muc_dich); ?>" maxlength="100" required>

            <input type="hidden" id="TongTien" name="TongTien" value="<?php echo $tong_tien_value; ?>">

            <h3>💰 Tổng Tiền Tạm Tính: <span id="tong-tien-hien-thi" style="color: <?php echo $thong_bao_loi ? 'red' : 'initial'; ?>;"><?php echo $tong_tien_hien_thi; ?></span></h3>

            <?php if ($thong_bao_loi): ?>
                <p style="color: red; font-weight: bold;"><?php echo $thong_bao_loi; ?></p>
            <?php endif; ?>

            <button type="submit" formaction="xu_ly_dat_phong.php" 
                    <?php echo ($is_valid && !empty($selected_rap) && !empty($selected_phong)) ? '' : 'disabled'; ?>>
                Tiếp Tục Đặt Phòng
            </button>
        </form>
    </div>
    </body>
</html>