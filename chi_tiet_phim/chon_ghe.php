<?php
session_start();
require "../Connection.php"; 

$ma_suat = $_GET['MaSuatChieu'] ?? die("Thiếu Mã Suất Chiếu.");
$ma_phim = $_GET['MaPhim'] ?? die("Thiếu Mã Phim.");

$ma_suat_safe = mysqli_real_escape_string($conn, $ma_suat);
$ma_phim_safe = mysqli_real_escape_string($conn, $ma_phim);

// 1. Lấy thông tin phim và suất chiếu
$sql_info = "SELECT P.TenPhim, R.TenRap, PCH.TenPhong, SC.ThoiGianBatDau, SC.GiaVeCoBan
             FROM suatchieu SC
             JOIN phim P ON SC.MaPhim = P.MaPhim
             JOIN phongchieu PCH ON SC.MaPhong = PCH.MaPhong
             JOIN rapchieu R ON PCH.MaRap = R.MaRap
             WHERE SC.MaSuatChieu = '$ma_suat_safe'";
             
$result_info = mysqli_query($conn, $sql_info);
if ($result_info === false) {
    die("Lỗi truy vấn thông tin suất chiếu: " . mysqli_error($conn));
}
$info = mysqli_fetch_assoc($result_info);
$ten_phim = $info['TenPhim'] ?? 'Phim';

// Lấy ghế đã đặt
$sql_dat = "SELECT MaGheDaChon FROM datve WHERE MaSuatChieu = '$ma_suat_safe' AND TrangThaiThanhToan = 'ThanhCong'";
$result_dat = mysqli_query($conn, $sql_dat); 

if ($result_dat === false) {
    die("Lỗi truy vấn ghế đã đặt: " . mysqli_error($conn) . " | SQL: " . $sql_dat);
}

// Khởi tạo mảng chứa các mã ghế đã đặt
$ghe_da_dat = []; 

if (mysqli_num_rows($result_dat) > 0) {
    while($row = mysqli_fetch_assoc($result_dat)) {
        if (!empty($row['MaGheDaChon'])) {
            $ghe_da_dat = array_merge($ghe_da_dat, explode(',', $row['MaGheDaChon']));
        }
    }
}
$ghe_da_dat = array_map('trim', $ghe_da_dat);
$ghe_da_dat = array_filter($ghe_da_dat); 

// 3. Lấy tất cả ghế trong phòng
$sql_ghe = "SELECT G.MaGhe, G.SoGhe, G.LoaiGhe
             FROM ghe G
             JOIN suatchieu SC ON G.MaPhong = SC.MaPhong
             WHERE SC.MaSuatChieu = '$ma_suat_safe'
             ORDER BY G.SoGhe";
             
$result_ghe = mysqli_query($conn, $sql_ghe);

if ($result_ghe === false) {
    die("Lỗi truy vấn tất cả ghế: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>2. Chọn Ghế - <?php echo $ten_phim; ?></title>
    <style>
        .seat { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center; margin: 5px; cursor: pointer; border: 1px solid #ccc; border-radius: 4px; transition: all 0.2s; font-size: 0.8em; }
        .available { background: #5cb85c; color: white; }
        .selected { background: #337ab7; color: white; transform: scale(1.1); }
        .booked { background: #d9534f; color: white; cursor: not-allowed; opacity: 0.6; }
        .screen { background: #333; color: white; padding: 10px; text-align: center; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🎬 Đặt Vé: <?php echo htmlspecialchars($ten_phim); ?></h1>
    <p>Rạp: <?php echo htmlspecialchars($info['TenRap']); ?> | Phòng: <?php echo htmlspecialchars($info['TenPhong']); ?> | Thời gian: <?php echo date('H:i d/m/Y', strtotime($info['ThoiGianBatDau'])); ?></p>
    
    <div class="screen">MÀN HÌNH</div>

    <form method="POST" action="thanh_toan.php">
        <input type="hidden" name="MaSuatChieu" value="<?php echo htmlspecialchars($ma_suat); ?>">
        <div class="seat-map">
            <?php while ($ghe = mysqli_fetch_assoc($result_ghe)): ?>
                <?php
                    // Kiểm tra ghế này có nằm trong danh sách ghế đã đặt $ghe_da_dat không
                    $is_booked = in_array($ghe['MaGhe'], $ghe_da_dat);
                    $class = $is_booked ? 'booked' : 'available';
                    $price_attr = $info['GiaVeCoBan'];
                ?>
                <label class="seat <?php echo $class; ?>">
                    <?php if (!$is_booked): ?>
                        <input type="checkbox" name="selected_seats[]" value="<?php echo htmlspecialchars($ghe['MaGhe']); ?>" style="display: none;" 
                                 data-price="<?php echo $price_attr; ?>">
                    <?php endif; ?>
                    <?php echo htmlspecialchars($ghe['SoGhe']); ?>
                </label>
            <?php endwhile; ?>
        </div>

        <hr>
        <h3>Tổng Tiền Tạm Tính: <span id="total_display">0</span> VND</h3>
        <button type="submit" id="btn-continue" disabled>Tiếp tục Thanh toán</button>
    </form>
    
    <script>
        const totalDisplay = document.getElementById('total_display');
        const btnContinue = document.getElementById('btn-continue');
        let currentTotal = 0;

        document.querySelectorAll('.seat.available').forEach(seat => {
            seat.addEventListener('click', function(e) {
                // ⭐ SỬA LỖI: Ngăn chặn hành vi mặc định của thẻ <label>
                // để tránh xung đột với việc toggle bằng JS
                e.preventDefault(); 
                
                const checkbox = this.querySelector('input[type="checkbox"]');
                const price = parseFloat(checkbox.getAttribute('data-price'));
                
                // ⭐ SỬA LỖI: Đảo trạng thái checkbox (Đã bị thiếu/xóa trong bản trước)
                checkbox.checked = !checkbox.checked;

                if (checkbox.checked) {
                    this.classList.remove('available');
                    this.classList.add('selected');
                    currentTotal += price;
                } else {
                    this.classList.remove('selected');
                    this.classList.add('available');
                    currentTotal -= price;
                }

                // Cập nhật hiển thị
                totalDisplay.textContent = currentTotal.toLocaleString('vi-VN');
                
                // Kích hoạt/Vô hiệu hóa nút tiếp tục
                btnContinue.disabled = currentTotal === 0;
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>