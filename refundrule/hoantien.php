<?php
session_start();
require "../Connection.php";

$ma_khach_hang = $_SESSION['user_id'] ?? null;
if (!$ma_khach_hang) {
    header("Location: ../Login&Register/Login.php");
    exit("Vui lòng đăng nhập để hoàn tiền.");
}

$ma_dat_ve = $_GET['MaDatVe'] ?? null;
if (!$ma_dat_ve) {
    die("Không tìm thấy mã đặt vé.");
}

// Lấy thông tin vé và suất chiếu
$sql = "SELECT dv.*, sc.ThoiGianBatDau 
         FROM datve dv 
         JOIN suatchieu sc ON dv.MaSuatChieu = sc.MaSuatChieu 
         WHERE dv.MaDatVe = '" . mysqli_real_escape_string($conn, $ma_dat_ve) . "' 
         AND dv.MaKhachHang = '" . mysqli_real_escape_string($conn, $ma_khach_hang) . "'";
$result = mysqli_query($conn, $sql);
$ve = mysqli_fetch_assoc($result);

if (!$ve) die("Không tìm thấy vé hoặc bạn không có quyền hoàn tiền vé này.");

// Tính số giờ còn lại trước suất chiếu
$now = new DateTime();
$suat_chieu = new DateTime($ve['ThoiGianBatDau']);
$diff_hours = ($suat_chieu->getTimestamp() - $now->getTimestamp()) / 3600;

// Lấy quy tắc hoàn tiền phù hợp
$sql_rule = "SELECT * FROM refundrule WHERE ThoiGianTruocSuatChieu <= $diff_hours ORDER BY ThoiGianTruocSuatChieu DESC LIMIT 1";
$result_rule = mysqli_query($conn, $sql_rule);
$rule = mysqli_fetch_assoc($result_rule);

if (!$rule) die("Không có quy tắc hoàn tiền phù hợp.");

$phan_tram_hoan = $rule['PhanTramHoan'];
$so_tien_hoan = round($ve['TongTien'] * $phan_tram_hoan / 100, 0);

// Xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy lý do hoàn
    $ly_do = $_POST['LyDoHoan'] ?? '';
    if ($ly_do === 'Khác') {
        $ly_do = trim($_POST['LyDoHoan_Khac'] ?? '');
    }

    // Tạo mã hoàn tiền tự động
    $sql_max = "SELECT MAX(CAST(SUBSTRING(MaHoanTien, 3) AS UNSIGNED)) AS max_ma FROM hoantien";
    $result_max = mysqli_query($conn, $sql_max);
    $row_max = mysqli_fetch_assoc($result_max);
    $next_ma = ($row_max['max_ma'] ?? 1000) + 1;
    $ma_hoan_tien = 'HT' . str_pad($next_ma, 4, '0', STR_PAD_LEFT);

    // Ghi nhận hoàn tiền
    $ngay_hoan = date('Y-m-d H:i:s');
    $is_loi_rap = 0; // 0: khách tự hủy, 1: lỗi rạp
    $trang_thai = 'Đang chờ xử lý';

    $sql_insert = "INSERT INTO hoantien (MaHoanTien, MaDatVe, MaQuyTac, NgayHoanTien, SoTienHoan, LyDoHoan, IsLoiRapChieu, TrangThaiHoan)
                    VALUES ('$ma_hoan_tien', '$ma_dat_ve', '{$rule['MaQuyTac']}', '$ngay_hoan', '$so_tien_hoan', '" . mysqli_real_escape_string($conn, $ly_do) . "', $is_loi_rap, '$trang_thai')";
    mysqli_query($conn, $sql_insert);

    // Chuyển hướng sang trang chi tiết hoàn tiền
    header("Location: chitiet_hoantien.php?MaHoanTien=" . urlencode($ma_hoan_tien));
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Yêu cầu hoàn vé</title>
    <style>
        :root {
            --primary-red: #cc0000;
            --dark-red: #a30000;
            --light-bg: #f8f9fa;
            --dark-text: #333;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding: 0;
            margin: 0;
        }

        /* NÚT BACK HÌNH TRÒN ĐỎ */
        .btn-back-fixed {
            position: fixed; 
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background-color: var(--primary-red); /* Màu nền đỏ */
            color: white; /* Màu mũi tên trắng */
            text-decoration: none;
            border-radius: 50%; /* Hình tròn */
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: background-color 0.2s, transform 0.2s;
            z-index: 1000;
        }
        .btn-back-fixed:hover {
            background-color: var(--dark-red);
            transform: scale(1.05);
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto; 
            padding: 30px;
            background: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            position: relative;
        }

        h2 {
            text-align: center;
            color: var(--primary-red);
            border-bottom: 3px solid var(--primary-red);
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        p {
            margin-bottom: 10px;
            font-size: 1em;
        }
        
        p strong {
            color: var(--dark-red);
        }

        .highlight-amount {
            font-size: 1.2em;
            color: var(--primary-red);
            font-weight: bold;
        }

        /* Form styling */
        form {
            padding-top: 20px;
            border-top: 1px solid #ccc;
            margin-top: 20px;
        }

        label {
            display: inline-block;
            margin-bottom: 8px;
            cursor: pointer;
            padding: 5px 0;
        }
        
        /* Radio button style (tùy chọn) */
        input[type="radio"] {
            accent-color: var(--primary-red);
            margin-right: 5px;
        }

        input[type="text"]#lydo_khac {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 80%;
            margin-top: 5px;
        }

        /* Button styles */
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
            transition: background-color 0.2s;
        }

        button[type="submit"] {
            background-color: var(--primary-red);
            color: white;
        }

        button[type="submit"]:hover {
            background-color: var(--dark-red);
        }

        button[type="button"] { /* Nút Hủy */
            background-color: #6c757d;
            color: white;
        }
        
        button[type="button"]:hover {
            background-color: #5a6268;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #6c757d;
            text-decoration: none;
        }
    </style>
    <script>
        function toggleOtherReason() {
            var selectedRadio = document.querySelector('input[name="LyDoHoan"]:checked');
            var otherInput = document.getElementById('lydo_khac');
            if (selectedRadio && selectedRadio.value === 'Khác') {
                otherInput.style.display = 'block';
                otherInput.required = true;
            } else {
                otherInput.style.display = 'none';
                otherInput.required = false;
            }
        }
        window.onload = function() {
            toggleOtherReason();
            var radios = document.querySelectorAll('input[name="LyDoHoan"]');
            radios.forEach(function(radio) {
                radio.addEventListener('change', toggleOtherReason);
            });
        };
    </script>
</head>

<body>
    <a href="javascript:history.back()" class="btn-back-fixed" title="Quay lại">
        <span>&larr;</span>
    </a>

    <div class="container">
        <h2>YÊU CẦU HOÀN VÉ</h2>
        <p><strong>Mã đặt vé:</strong> <?php echo htmlspecialchars($ve['MaDatVe']); ?></p>
        <p><strong>Ghế:</strong> <?php echo htmlspecialchars($ve['MaGheDaChon']); ?></p>
        <p><strong>Tổng tiền vé:</strong> <?php echo number_format($ve['TongTien'], 0, ',', '.'); ?> VNĐ</p>
        <p><strong>Phần trăm hoàn:</strong> <span class="highlight-amount"><?php echo $phan_tram_hoan; ?>%</span></p>
        <p><strong>Số tiền hoàn dự kiến:</strong> <span class="highlight-amount"><?php echo number_format($so_tien_hoan, 0, ',', '.'); ?> VNĐ</span></p>

        <form method="post">
            <p><strong>Lý do hoàn vé:</strong></p>
            <label><input type="radio" name="LyDoHoan" value="Không hài lòng với lựa chọn" checked> Không hài lòng với lựa chọn</label><br>
            <label><input type="radio" name="LyDoHoan" value="Đặt nhầm suất chiếu"> Đặt nhầm suất chiếu</label><br>
            <label><input type="radio" name="LyDoHoan" value="Thay đổi kế hoạch"> Thay đổi kế hoạch</label><br>
            <label><input type="radio" name="LyDoHoan" value="Khác"> Khác</label>
            <input type="text" name="LyDoHoan_Khac" id="lydo_khac" style="display:none; margin-left:10px;" placeholder="Nhập lý do khác">
            <br><br>
            <button type="submit">Xác nhận yêu cầu hoàn vé</button>
            <button type="button" onclick="window.history.back();">Hủy</button>
        </form>
        
        <a href="../chi_tiet_phim/lich_su_dat_ve.php" class="back-link">Quay lại lịch sử vé</a>
    </div>
</body>

</html>
<?php mysqli_close($conn); ?>