<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh'); 

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
    header("Location: ../Login&Register/Login.php");
    exit;
}
$ma_khach_hang = $_SESSION['user_id'];
require "../Connection.php"; 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../layout/header.php'; 

$selected_rap = $_POST['MaRap'] ?? ''; 
$selected_phong = $_POST['MaPhong'] ?? '';
$bat_dau = $_POST['ThoiGianBatDau'] ?? '';
$ket_thuc = $_POST['ThoiGianKetThuc'] ?? '';
$selected_phim = $_POST['MaPhim'] ?? 'none';
$muc_dich = $_POST['MucDichThue'] ?? 'Tiệc sinh nhật';

if (isset($conn)) {
    $sql_rap = "SELECT MaRap, TenRap FROM rapchieu ORDER BY TenRap";
    $result_rap = mysqli_query($conn, $sql_rap);

    $sql_phong = "SELECT MaPhong, MaRap, TenPhong FROM phongchieu WHERE LoaiPhong = 'VIP' ORDER BY MaRap, TenPhong";
    $result_phong = mysqli_query($conn, $sql_phong);
    $phong_vip_data = mysqli_fetch_all($result_phong, MYSQLI_ASSOC); 

    $sql_phim = "SELECT MaPhim, TenPhim FROM phim WHERE NgayKhoiChieu <= CURDATE() ORDER BY TenPhim"; 
    $result_phim = mysqli_query($conn, $sql_phim);
} else {
    $result_rap = null;
    $phong_vip_data = [];
    $result_phim = null;
}


$GIA_CO_BAN_MOI_GIO = 500000; 
$PHU_PHI_PHIM = 100000; 

$tong_tien_hien_thi = "0 VND";
$tong_tien_value = 0;
$thong_bao_loi = '';
$is_valid = false;

if (!empty($bat_dau) && !empty($ket_thuc)) {
    $start_ts = strtotime($bat_dau);
    $end_ts = strtotime($ket_thuc);
    $now_ts = time() + 60; 

    if ($start_ts < $now_ts) {
        $thong_bao_loi = "LỖI: Thời gian bắt đầu phải ở tương lai.";
    } elseif ($start_ts >= $end_ts) {
        $thong_bao_loi = "LỖI: Thời gian kết thúc phải sau thời gian bắt đầu.";
    } else {
        $duration_seconds = $end_ts - $start_ts;
        $duration_hours = $duration_seconds / 3600;

        if ($duration_hours > 3.01) { 
            $thong_bao_loi = "LỖI: Thời gian thuê tối đa là 3 giờ.";
        } else {
            $so_gio_tinh_tien = ceil($duration_hours);
            $tong_tien_value = $so_gio_tinh_tien * $GIA_CO_BAN_MOI_GIO;

            if ($selected_phim !== 'none') {
                $tong_tien_value += $PHU_PHI_PHIM;
            }

            $tong_tien_hien_thi = number_format($tong_tien_value, 0, ',', '.') . ' VND';
            if (!empty($selected_rap) && !empty($selected_phong)) {
                 $is_valid = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Thuê Phòng VIP Riêng - CGV</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }
        .wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            max-width: none;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            height: 140px;
            width: 100%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 0;
            box-sizing: border-box; 
        }
                .form_tim {
            display: flex;
            gap: 3%;
        }

        .form_tim input {
            width: 60%;
            border-radius: 10px;

        }
        .form_tim button {
            height: 5vh;
            border-radius: 10px;
        }
        .form_tim button:hover {
            background-color: #a3182d;
            color:rgba(247, 242, 242, 0.9);
        }
        .menu li.search-box {
            margin-left: 15%;
            flex-grow: 1;
        }
        .logo {
            font-size: 36px;
            font-weight: bold;
            color: #d11e3b;
            text-transform: uppercase;
        }
        .menu {
            height: 50px;
            width: 100%;
            background: #d11e3b;
            box-sizing: border-box;
        }
        .menu ul {
            list-style: none;
            margin: 0 auto;
            padding: 0 20px; 
            display: flex;
            align-items: center;
            height: 100%;
        }
        .menu li {
            padding: 0 15px;
        }
        .menu li a {
            text-decoration: none;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            padding: 15px 5px;
            transition: 0.3s;
        }
        .menu li a:hover {
            background-color: #a3182d;
        }
        .menu li form {
            margin: 0;
        }
        .menu select {
            padding: 5px;
            border-radius: 3px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        
        .page-vip-booking .menu ul li.search-item,
        .page-vip-booking .menu ul li:nth-last-child(1) { 
        }
        .content-container {
            margin: 0 auto;
            padding: 0 20px; 
            box-sizing: border-box;
            background: #fff;
        }

        .main {
            min-height: 400px;
            padding: 20px 0;
            box-sizing: border-box;
            background: #fff;
        }

        h2 {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 30px 0 15px 0; 
            border-bottom: 2px solid #d11e3b;
            padding-bottom: 10px;
        }
        #datphong-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            background: #fff;
        }
        #datphong-form label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        #datphong-form select,
        #datphong-form input[type="datetime-local"],
        #datphong-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        #datphong-form h3 {
            margin-top: 30px;
            font-size: 20px;
            color: #d11e3b;
            border-top: 1px dashed #ccc;
            padding-top: 15px;
        }
        #datphong-form button {
            background-color: #d11e3b;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            width: 100%;
            transition: background-color 0.3s;
        }
        #datphong-form button:hover:not([disabled]) {
            background-color: #a3182d;
        }
        #datphong-form button[disabled] {
            background-color: #aaa;
            cursor: not-allowed;
        }
        
        .footer {
            height: 100px;
            width: 100%;
            background: #222;
            color: #ccc;
            padding: 20px 0;
            text-align: center;
            font-size: 12px;
            box-sizing: border-box;
        }
        .error-message {
            color: red; 
            font-weight: bold; 
            margin-top: -5px; 
            margin-bottom: 10px;
        }

    </style>
</head>

<body class="page-vip-booking"> 
<div class="wrapper">

    <div class="content-container">
        <div class="main">
            <h2>📅 Đặt Thuê Phòng VIP Riêng</h2>
            <form id="datphong-form" action="" method="POST"> 
                
                <input type="hidden" name="MaKhachHang" value="<?php echo htmlspecialchars($ma_khach_hang); ?>">

                <label for="MaRap">Chọn Rạp Chiếu:</label>
                <select name="MaRap" id="MaRap" required onchange="this.form.submit()"> 

                    <option value="">-- Chọn Rạp --</option>
                    <?php 
                    if ($result_rap) {
                        mysqli_data_seek($result_rap, 0); 
                        while ($row = mysqli_fetch_assoc($result_rap)): ?>
                            <option value="<?php echo $row['MaRap']; ?>" 
                                    <?php echo ($row['MaRap'] == $selected_rap) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['TenRap']); ?>
                            </option>
                        <?php endwhile; 
                    } ?>
                </select>
                
                <label for="MaPhong">Chọn Phòng VIP:</label>
                <select name="MaPhong" id="MaPhong" required>
                    <option value="">-- Chọn Phòng --</option>
                    <?php 
                    $rap_found = false;
                    foreach ($phong_vip_data as $row): 
                        if ($row['MaRap'] == $selected_rap || empty($selected_rap)):
                            $rap_found = true;
                            $selected = ($row['MaPhong'] == $selected_phong) ? 'selected' : '';
                        ?>
                        <option class="phong-option" 
                                value="<?php echo $row['MaPhong']; ?>" 
                                data-rap="<?php echo $row['MaRap']; ?>"
                                <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($row['TenPhong']); ?>
                        </option>
                    <?php 
                        endif;
                    endforeach; 
                    if (!empty($selected_rap) && !$rap_found) {
                        echo '<option value="" disabled>Không có phòng VIP tại rạp này</option>';
                    }
                    ?>
                </select>

                <label for="ThoiGianBatDau">Thời Gian Bắt Đầu:</label>
                <input type="datetime-local" id="ThoiGianBatDau" name="ThoiGianBatDau" required 
                        value="<?php echo htmlspecialchars($bat_dau); ?>" 
                        min="<?php echo date('Y-m-d\TH:i'); ?>">

                <label for="ThoiGianKetThuc">Thời Gian Kết Thúc (Tối đa 3 giờ):</label>
                <input type="datetime-local" id="ThoiGianKetThuc" name="ThoiGianKetThuc" required 
                        value="<?php echo htmlspecialchars($ket_thuc); ?>"
                        >
                
                <label for="MaPhim">Chọn Phim (Tùy chọn cho sự kiện/chiếu riêng):</label>
                <select name="MaPhim" id="MaPhim">
                    <option value="none" <?php echo ($selected_phim == 'none') ? 'selected' : ''; ?>>-- Không Chiếu Phim (Sự kiện khác) --</option>
                    <?php 
                    if ($result_phim) {
                         mysqli_data_seek($result_phim, 0); 
                         while ($row = mysqli_fetch_assoc($result_phim)): ?>
                            <option value="<?php echo $row['MaPhim']; ?>" <?php echo ($row['MaPhim'] == $selected_phim) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['TenPhim']); ?>
                            </option>
                        <?php endwhile; 
                    }?>
                </select>

                <label for="MucDichThue">Mục Đích Thuê:</label>
                <input type="text" id="MucDichThue" name="MucDichThue" value="<?php echo htmlspecialchars($muc_dich); ?>" maxlength="100" required>

                <input type="hidden" id="TongTien" name="TongTien" value="<?php echo $tong_tien_value; ?>">

                <h3>💰 Tổng Tiền Tạm Tính: 
                    <span id="tong-tien-hien-thi" 
                          style="color: <?php echo $thong_bao_loi ? 'red' : '#d11e3b'; ?>;">
                        <?php echo $tong_tien_hien_thi; ?>
                    </span>
                </h3>
                <p id="error-message-js" class="error-message" style="display: none;"></p>
                <?php if ($thong_bao_loi): ?>
                    <p class="error-message"><?php echo $thong_bao_loi; ?></p>
                <?php endif; ?>

                <button type="submit" formaction="xu_ly_dat_phong.php" 
                        id="submit-button"
                        <?php echo ($is_valid && !empty($selected_rap) && !empty($selected_phong) && empty($thong_bao_loi)) ? '' : 'disabled'; ?>>
                    Tiếp Tục Đặt Phòng
                </button>
            </form>
        </div>
    </div>

    <div class="footer">© 2025 Quản lý Rạp Phim. All rights reserved.</div>
</div>

<script>
    const GIA_CO_BAN_MOI_GIO = <?php echo $GIA_CO_BAN_MOI_GIO; ?>;
    const PHU_PHI_PHIM = <?php echo $PHU_PHI_PHIM; ?>;
    const MAX_DURATION_HOURS = 3;

    const maPhongSelect = document.getElementById('MaPhong');
    const timeStartInput = document.getElementById('ThoiGianBatDau');
    const timeEndInput = document.getElementById('ThoiGianKetThuc');
    const maPhimSelect = document.getElementById('MaPhim');
    const tongTienHienThi = document.getElementById('tong-tien-hien-thi');
    const tongTienHidden = document.getElementById('TongTien');
    const submitButton = document.getElementById('submit-button');
    const errorMessageJs = document.getElementById('error-message-js');
    
    function formatCurrency(amount) {
        return amount.toLocaleString('vi-VN') + ' VND';
    }

    // Hàm chính tính toán giá và kiểm tra hợp lệ
    function calculatePriceAndValidate() {
        // Lấy giá trị mới nhất của các trường
        const startTimeValue = timeStartInput.value;
        const endTimeValue = timeEndInput.value;
        const maRapValue = document.getElementById('MaRap').value;
        const maPhongValue = maPhongSelect.value;
        
        const startTime = new Date(startTimeValue);
        const endTime = new Date(endTimeValue);
        const now = new Date();
        const nowPlus1Min = new Date(now.getTime() + 60000); 
        
        let isValid = true;
        let errorMessage = '';
        let tongTien = 0;

        // 1. Kiểm tra Rạp và Phòng đã chọn chưa
        if (!maRapValue || !maPhongValue) {
            errorMessage = "Vui lòng chọn Rạp và Phòng VIP.";
            isValid = false;
        }

        // 2. Kiểm tra thời gian
        if (!startTimeValue || !endTimeValue) {
            tongTienHienThi.textContent = '0 VND';
            tongTienHidden.value = 0;
            if (isValid) {
               errorMessage = "Vui lòng chọn đầy đủ thời gian bắt đầu và kết thúc.";
               isValid = false;
            }
        } else if (startTime < nowPlus1Min) {
            errorMessage = "Thời gian bắt đầu phải ở tương lai.";
            isValid = false;
        } else if (startTime >= endTime) {
            errorMessage = "Thời gian kết thúc phải sau thời gian bắt đầu.";
            isValid = false;
        } else {
            const durationSeconds = (endTime.getTime() - startTime.getTime()) / 1000;
            const durationHours = durationSeconds / 3600;

            if (durationHours > MAX_DURATION_HOURS + 0.01) { // Lớn hơn 3 giờ 
                errorMessage = `Thời gian thuê tối đa là ${MAX_DURATION_HOURS} giờ.`;
                isValid = false;
            } else {
                // 3. Tính toán giá
                const soGioTinhTien = Math.ceil(durationHours); // Làm tròn lên giờ
                tongTien = soGioTinhTien * GIA_CO_BAN_MOI_GIO;

                // Thêm phụ phí phim
                if (maPhimSelect.value !== 'none') {
                    tongTien += PHU_PHI_PHIM;
                }
                
                tongTienHidden.value = tongTien;
                tongTienHienThi.textContent = formatCurrency(tongTien);
                tongTienHienThi.style.color = '#d11e3b';
            }
        }
        
        // 4. Cập nhật trạng thái nút và thông báo lỗi
        if (isValid && tongTien > 0) {
            submitButton.disabled = false;
            errorMessageJs.style.display = 'none';
        } else {
            submitButton.disabled = true;
            if (errorMessage) {
                errorMessageJs.textContent = errorMessage;
                errorMessageJs.style.display = 'block';
                tongTienHienThi.style.color = 'red';
            } else {
                errorMessageJs.style.display = 'none';
            }
        }
    }

    maPhongSelect.addEventListener('change', calculatePriceAndValidate);
    timeStartInput.addEventListener('change', calculatePriceAndValidate);
    timeEndInput.addEventListener('change', calculatePriceAndValidate);
    maPhimSelect.addEventListener('change', calculatePriceAndValidate);
    
    document.addEventListener('DOMContentLoaded', () => {
        calculatePriceAndValidate();
    
        const phpError = document.querySelector('.error-message');
        if (phpError && phpError !== errorMessageJs) {
             phpError.style.display = 'none';
        }
    });

</script>

<?php
if (isset($conn)) { mysqli_close($conn); }
?>

</body>
</html>