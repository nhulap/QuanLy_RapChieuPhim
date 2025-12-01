<?php
session_start();
require "../Connection.php"; 

// --- Lấy thông tin ---
$ma_suat = $_GET['MaSuatChieu'] ?? die("Thiếu Mã Suat Chiếu.");
$ma_phim = $_GET['MaPhim'] ?? die("Thiếu Mã Phim.");

$ma_suat_safe = mysqli_real_escape_string($conn, $ma_suat);
$ma_phim_safe = mysqli_real_escape_string($conn, $ma_phim);

// 1. Lấy thông tin phim, rạp, phòng, giá vé
$sql_info = "SELECT P.TenPhim, R.TenRap, PCH.TenPhong, SC.ThoiGianBatDau, SC.GiaVeCoBan
             FROM suatchieu SC
             JOIN phim P ON SC.MaPhim = P.MaPhim
             JOIN phongchieu PCH ON SC.MaPhong = PCH.MaPhong
             JOIN rapchieu R ON PCH.MaRap = R.MaRap
             WHERE SC.MaSuatChieu = '$ma_suat_safe'";
             
$result_info = mysqli_query($conn, $sql_info);
if (!$result_info || mysqli_num_rows($result_info) == 0) {
    die("Không tìm thấy thông tin suất chiếu.");
}
$info = mysqli_fetch_assoc($result_info);
$ten_phim = $info['TenPhim'] ?? 'Phim';
$gia_ve_co_ban = (float)($info['GiaVeCoBan'] ?? 0);
$thoi_gian = date('H:i d/m/Y', strtotime($info['ThoiGianBatDau']));

// 2. Lấy ghế đã đặt (Logic của bạn đã đúng)
$sql_dat = "SELECT MaGheDaChon FROM datve WHERE MaSuatChieu = '$ma_suat_safe' AND TrangThaiThanhToan = 'ThanhCong'";
$result_dat = mysqli_query($conn, $sql_dat); 

$ghe_da_dat = []; 
if ($result_dat) {
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
             ORDER BY G.MaGhe"; // Nên sắp xếp theo MaGhe để đảm bảo thứ tự
             
$result_ghe = mysqli_query($conn, $sql_ghe);
$page_title = "Chọn Ghế: " . $ten_phim;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        :root {
            --primary-red: #d11e3b;
            --dark-red: #a3182d;
            --primary-blue: #337ab7;
            --booked-red: #d9534f;
            --selected-green: #5cb85c;
            --background-grey: #f0f0f0;
            --text-dark: #333;
        }

        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: var(--background-grey); color: var(--text-dark); }
        .wrapper { max-width: 1200px; margin: 20px auto; background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        .btn-back-fixed {
            position: fixed; 
            top: 20px; 
            left: 20px; 
            width: 45px;
            height: 45px;
            border-radius: 50%;
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

        .header-bar { 
            background-color: var(--primary-red); 
            color: white; 
            padding: 20px; 
            border-top-left-radius: 8px; 
            border-top-right-radius: 8px;            padding-left: 70px; 
        }
        .header-bar h1 { margin: 0; font-size: 2em; }


        .movie-info { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; font-size: 1.1em; }
        .movie-info p { margin: 0; }

        /* --- SEAT MAP --- */
        .seat-section { padding: 30px 20px; text-align: center; }
        .screen { background: #555; color: white; padding: 15px; margin-bottom: 30px; font-weight: bold; border-radius: 5px; width: 80%; margin-left: auto; margin-right: auto; box-shadow: 0 0 10px rgba(0,0,0,0.3); }
        
        .seat-map-grid {
            display: inline-grid;
            grid-template-columns: repeat(15, 1fr); 
            gap: 5px;
            margin: 0 auto;
        }

        .seat-label {
            display: block; 
            width: 40px; 
            height: 40px; 
            line-height: 40px; 
            text-align: center; 
            cursor: pointer; 
            border: 1px solid var(--text-dark); 
            border-radius: 4px; 
            transition: all 0.2s; 
            font-size: 0.8em; 
            font-weight: bold;
        }
        

        .available { background: #fcfcfc; color: var(--text-dark); border-color: #ccc; }
        .selected { background: var(--selected-green); color: white; border-color: var(--selected-green); transform: scale(1.05); box-shadow: 0 0 8px rgba(92, 184, 92, 0.5); }
        .booked { background: var(--booked-red); color: white; cursor: not-allowed; opacity: 0.7; border-color: var(--booked-red); }
        .booked:hover { transform: none; box-shadow: none; }
        

        .legend, .summary { 
            padding: 15px 20px; 
            background: var(--background-grey); 
            border-radius: 5px; 
            margin-top: 30px;
        }
        .legend-item { display: inline-flex; align-items: center; margin-right: 20px; font-size: 0.9em; }
        .legend-color { width: 15px; height: 15px; margin-right: 8px; border-radius: 3px; border: 1px solid #ccc; }
        
        .summary { margin-top: 20px; padding: 25px 30px; border: 2px solid var(--primary-red); background: #fff; }
        .summary h3 { margin-top: 0; color: var(--primary-red); font-size: 1.5em; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1.1em; }
        .summary-row strong { color: var(--dark-red); }
        
        .btn-continue {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-red);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 15px;
        }
        .btn-continue:hover:not(:disabled) { background-color: var(--dark-red); }
        .btn-continue:disabled { background-color: #ccc; cursor: not-allowed; }

        @media (max-width: 768px) {
            .movie-info { flex-direction: column; align-items: flex-start; }
            .movie-info p { margin-bottom: 5px; }
            .seat-map-grid { grid-template-columns: repeat(10, 1fr); }
            .seat-label { width: 30px; height: 30px; line-height: 30px; }
            .header-bar { padding-left: 60px; }
            .btn-back-fixed {
                top: 15px; 
                left: 15px;
                width: 40px;
                height: 40px;
                line-height: 40px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    
    <a href="chon_rap_suat.php?MaPhim=<?php echo urlencode($ma_phim); ?>" class="btn-back-fixed" title="Quay lại chọn suất chiếu">
        <span>&larr;</span>
    </a>

    <div class="wrapper">
        <div class="header-bar">
            <h1> <?php echo htmlspecialchars($ten_phim); ?>
            </h1>
        </div>

        <div class="movie-info">
            <p><strong>Rạp:</strong> <?php echo htmlspecialchars($info['TenRap']); ?> | <strong>Phòng:</strong> <?php echo htmlspecialchars($info['TenPhong']); ?></p>
            <p><strong>Thời gian:</strong> <?php echo $thoi_gian; ?> | <strong>Giá cơ bản:</strong> <?php echo number_format($gia_ve_co_ban, 0, ',', '.'); ?> VND</p>
        </div>

        <div class="seat-section">
            <div class="screen">MÀN HÌNH</div>

            <form method="POST" action="thanh_toan.php">
                <input type="hidden" name="MaSuatChieu" value="<?php echo htmlspecialchars($ma_suat); ?>">
                <input type="hidden" name="MaPhim" value="<?php echo htmlspecialchars($ma_phim); ?>">
                
                <div class="seat-map-grid">
                    <?php 
                    while ($ghe = mysqli_fetch_assoc($result_ghe)): 
                        $is_booked = in_array($ghe['MaGhe'], $ghe_da_dat);
                        $class = $is_booked ? 'booked' : 'available';
                    ?>
                        <label class="seat-label <?php echo $class; ?>">
                            <?php if (!$is_booked): ?>
                                <input type="checkbox" name="selected_seats[]" value="<?php echo htmlspecialchars($ghe['MaGhe']); ?>" 
                                       style="display: none;" 
                                       data-price="<?php echo $gia_ve_co_ban; ?>">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($ghe['SoGhe']); ?>
                        </label>
                    <?php endwhile; ?>
                </div>

                <div class="legend">
                    <div class="legend-item"><span class="legend-color available" style="border-color:#ccc;"></span> Ghế Trống</div>
                    <div class="legend-item"><span class="legend-color selected" style="background: var(--selected-green); border-color:var(--selected-green);"></span> Đang Chọn</div>
                    <div class="legend-item"><span class="legend-color booked" style="background: var(--booked-red); border-color:var(--booked-red);"></span> Đã Bán</div>
                </div>

                <div class="summary">
                    <h3>Tóm Tắt Đặt Vé</h3>
                    <div class="summary-row">
                        <span>Số lượng ghế:</span>
                        <strong id="seat_count">0</strong>
                    </div>
                    <div class="summary-row">
                        <span>Ghế đã chọn:</span>
                        <strong id="seats_list">Chưa chọn</strong>
                    </div>
                    <div class="summary-row" style="font-size: 1.3em; margin-top: 15px;">
                        <span>TỔNG TIỀN TẠM TÍNH:</span>
                        <strong id="total_display" style="color: var(--primary-red);">0 VND</strong>
                    </div>
                    <button type="submit" id="btn-continue" class="btn-continue" disabled>
                        TIẾP TỤC THANH TOÁN
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const totalDisplay = document.getElementById('total_display');
        const seatCountDisplay = document.getElementById('seat_count');
        const seatsListDisplay = document.getElementById('seats_list');
        const btnContinue = document.getElementById('btn-continue');
        let currentTotal = 0;
        let selectedSeats = [];

        document.querySelectorAll('.seat-label.available').forEach(seatLabel => {
            const checkbox = seatLabel.querySelector('input[type="checkbox"]');
            const seatName = seatLabel.textContent.trim(); 
            const price = parseFloat(checkbox.getAttribute('data-price'));

            seatLabel.addEventListener('click', function(e) {
                // Ngăn chặn hành vi mặc định của label để kiểm soát việc chuyển đổi
                e.preventDefault(); 
                
                // Đảo trạng thái checkbox
                checkbox.checked = !checkbox.checked;

                if (checkbox.checked) {
                    this.classList.remove('available');
                    this.classList.add('selected');
                    currentTotal += price;
                    selectedSeats.push(seatName);
                } else {
                    this.classList.remove('selected');
                    this.classList.add('available');
                    currentTotal -= price;
                    selectedSeats = selectedSeats.filter(name => name !== seatName);
                }

                // Cập nhật hiển thị
                seatCountDisplay.textContent = selectedSeats.length;
                seatsListDisplay.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'Chưa chọn';
                
                totalDisplay.textContent = currentTotal.toLocaleString('vi-VN') + ' VND';
                
                // Kích hoạt/Vô hiệu hóa nút tiếp tục
                btnContinue.disabled = selectedSeats.length === 0;
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>