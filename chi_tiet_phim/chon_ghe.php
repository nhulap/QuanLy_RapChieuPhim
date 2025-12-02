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
$gia_ve_co_ban = (float)($info['GiaVeCoBan'] ?? 90000);

// 2. Lấy ghế đã đặt (Giữ nguyên logic của bạn)
$sql_dat = "
    SELECT dv.MaGheDaChon
    FROM datve dv
    WHERE dv.MaSuatChieu = '$ma_suat_safe'
      AND dv.TrangThaiThanhToan = 'Thanh Toán Thành Công'
      AND NOT EXISTS (
          SELECT 1 FROM hoantien ht
          WHERE ht.MaDatVe = dv.MaDatVe
            AND ht.TrangThaiHoan = 'Hoàn Tiền Thành Công'
      )
";
$result_dat = mysqli_query($conn, $sql_dat);

if ($result_dat === false) {
    // Để tránh lỗi nếu bảng hoantien không tồn tại, bạn có thể comment dòng này nếu cần
    // die("Lỗi truy vấn ghế đã đặt: " . mysqli_error($conn) . " | SQL: " . $sql_dat); 
}

$ghe_da_dat = [];
if ($result_dat) {
    while ($row = mysqli_fetch_assoc($result_dat)) {
        if (!empty($row['MaGheDaChon'])) {
            $ghe_da_dat = array_merge($ghe_da_dat, explode(',', $row['MaGheDaChon']));
        }
    }
}
$ghe_da_dat = array_map('trim', $ghe_da_dat);
$ghe_da_dat = array_filter($ghe_da_dat);


// 3. Lấy tất cả ghế trong phòng và NHÓM THEO HÀNG
$sql_ghe = "SELECT G.MaGhe, G.SoGhe, G.LoaiGhe
             FROM ghe G
             JOIN suatchieu SC ON G.MaPhong = SC.MaPhong
             WHERE SC.MaSuatChieu = '$ma_suat_safe'
             ORDER BY G.SoGhe"; 

$result_ghe = mysqli_query($conn, $sql_ghe);
$ghe_theo_hang = [];
$max_cols = 0; 

if ($result_ghe) {
    while ($ghe = mysqli_fetch_assoc($result_ghe)) {
        if (preg_match('/^([a-zA-Z]+)/', $ghe['SoGhe'], $matches)) {
            $hang = strtoupper($matches[1]);
        } else {
            $hang = 'Z'; 
        }
        
        $ghe_theo_hang[$hang][] = $ghe;
        $max_cols = max($max_cols, count($ghe_theo_hang[$hang]));
    }
}
ksort($ghe_theo_hang);

?>

<!DOCTYPE html>
<html>

<head>
    <title>2. Chọn Ghế - <?php echo $ten_phim; ?></title>
    <style>
        /* Định nghĩa màu chủ đạo */
        :root {
            --primary-red: #cc0000; /* Đỏ cho ghế đang chọn và nút */
            --dark-red: #a30000; /* Giá trị đã được thêm vào */
            --booked-color: #ffffff; /* MÀU TRẮNG cho ghế Đã Đặt */
            --selected-color: var(--primary-red); /* MÀU ĐỎ cho ghế Đang Chọn */
            --available-color: #5cb85c; /* MÀU XANH LÁ cho ghế Chưa Chọn */
            --text-color-dark: #343a40;
            --text-color-light: #ffffff;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color-dark);
            padding: 20px;
            text-align: center;
        }

        h1 {
            color: var(--primary-red);
            border-bottom: 2px solid var(--primary-red);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        /* --- KHU VỰC CHỌN GHẾ --- */
        .seat-map-container {
            border: 2px solid #ccc; 
            padding: 20px;
            border-radius: 8px;
            background: white;
            /* ĐIỀU CHỈNH: Kéo dài chiều rộng */
            width: 90%; 
            max-width: 1000px; 
            margin: 20px auto;
        }

        .seat-map-grid {
            display: grid;
            gap: 8px;
            width: 100%; 
        }

        .seat {
            display: block; 
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 4px;
            transition: all 0.2s;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        /* Ghế Chưa Chọn (AVAILABLE) - MÀU XANH LÁ */
        .available {
            background: var(--available-color); 
            color: var(--text-color-light); 
            border-color: var(--available-color);
        }
        .available:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        /* Ghế Đang Chọn (SELECTED) - MÀU ĐỎ */
        .selected {
            background: var(--selected-color); /* ĐỎ */
            color: var(--text-color-light); /* Chữ TRẮNG */
            border: 2px solid var(--selected-color); 
            transform: scale(1.1);
        }

        /* Ghế Đã Đặt (BOOKED) - MÀU TRẮNG */
        .booked {
            background: var(--booked-color); /* TRẮNG */
            color: var(--text-color-dark); /* Chữ ĐEN để nhìn rõ */
            cursor: not-allowed;
            opacity: 0.8;
            border: 1px solid #ccc; /* Thêm viền để nhìn rõ trên nền trắng */
        }

        /* Label Hàng */
        .row-label {
            background: #ccc; 
            border: none; 
            cursor: default; 
            color: var(--text-color-dark); 
            font-size: 1em;
            font-weight: bold;
        }

        .screen {
            background: #333;
            color: white;
            padding: 15px;
            text-align: center;
            margin: 20px auto;
            font-weight: bold;
            width: 80%;
            border-radius: 5px;
        }

        /* --- Ô TỔNG TIỀN (SUMMARY BOX) --- */
        .summary-box {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            width: 90%; 
            max-width: 1000px; 
            margin-left: auto;
            margin-right: auto;
        }
        .summary-box h3 {
            color: var(--primary-red);
            margin-top: 0;
        }
        #total_display {
            color: var(--primary-red);
            font-size: 1.2em;
            font-weight: bold;
        }

        /* Style cho nút */
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
            transition: background-color 0.2s;
        }

        #btn-continue {
            background-color: var(--primary-red);
            color: white;
        }
        #btn-continue:hover:not(:disabled) {
            background-color: var(--dark-red);
        }
        #btn-continue:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        button[onclick="window.history.back();"] {
            background-color: #6c757d; 
            color: white;
        }
        button[onclick="window.history.back();"]:hover {
            background-color: #5a6268;
        }
    </style>
</head>

<body>
    <h1>🎬 Đặt Vé: <?php echo htmlspecialchars($ten_phim); ?></h1>
    <p>Rạp: **<?php echo htmlspecialchars($info['TenRap']); ?>** | Phòng: **<?php echo htmlspecialchars($info['TenPhong']); ?>** | Thời gian: **<?php echo date('H:i d/m/Y', strtotime($info['ThoiGianBatDau'])); ?>**</p>

    <div class="screen">MÀN HÌNH</div>

    <form method="POST" action="thanh_toan.php">
        <input type="hidden" name="MaSuatChieu" value="<?php echo htmlspecialchars($ma_suat); ?>">
        
        <div class="seat-map-container">
            <div class="seat-map-grid" style="grid-template-columns: 45px repeat(<?php echo $max_cols; ?>, 1fr);">
                
                <?php 
                // Duyệt qua từng Hàng (A, B, C...)
                foreach ($ghe_theo_hang as $hang_ghe => $danh_sach_ghe): 
                ?>
                    <div class="seat row-label">
                        <?php echo htmlspecialchars($hang_ghe); ?>
                    </div>

                    <?php 
                    foreach ($danh_sach_ghe as $ghe): 
                        // Kiểm tra ghế này có nằm trong danh sách ghế đã đặt $ghe_da_dat không
                        $is_booked = in_array($ghe['SoGhe'], $ghe_da_dat); 
                        $class = $is_booked ? 'booked' : 'available';
                        $price_attr = $gia_ve_co_ban;
                    ?>
                        <label class="seat <?php echo $class; ?>">
                            <?php if (!$is_booked): ?>
                                <input type="checkbox" name="selected_seats[]" value="<?php echo htmlspecialchars($ghe['MaGhe']); ?>" style="display: none;"
                                    data-price="<?php echo $price_attr; ?>">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($ghe['SoGhe']); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="summary-box">
            <h3>Tổng Tiền Tạm Tính: <span id="total_display">0</span> VND</h3>
            <button type="submit" id="btn-continue" disabled>Tiếp tục Thanh toán</button>
            <button type="button" onclick="window.history.back();">Hủy</button>
        </div>
    </form>

    <script>
        const totalDisplay = document.getElementById('total_display');
        const btnContinue = document.getElementById('btn-continue');
        const seatCheckboxes = document.querySelectorAll('.seat.available input[type="checkbox"]');
        const pricePerSeat = <?php echo (int)($info['GiaVeCoBan'] ?? 90000); ?>;

        function updateTotal() {
            let selectedCount = 0;
            seatCheckboxes.forEach(cb => {
                const label = cb.closest('.seat');
                if (cb.checked) {
                    selectedCount++;
                    // Màu ĐỎ (selected)
                    label.classList.remove('available');
                    label.classList.add('selected');
                } else {
                    // Màu XANH LÁ (available)
                    label.classList.remove('selected');
                    label.classList.add('available');
                }
            });
            
            totalDisplay.textContent = (selectedCount * pricePerSeat).toLocaleString('vi-VN');
            btnContinue.disabled = selectedCount === 0;
        }

        seatCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateTotal);
            
            // Xử lý click trên ghế đã đặt để ngăn chặn hành vi mặc định
            cb.closest('.seat').addEventListener('click', function(e) {
                if (this.classList.contains('booked')) {
                    e.preventDefault(); 
                }
            });
        });

        updateTotal();
    </script>
</body>

</html>
<?php mysqli_close($conn); ?>