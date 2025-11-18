<?php
session_start();
require "../Connection.php"; 

$ma_phim = $_GET['MaPhim'] ?? die("Thiếu Mã Phim.");
$ma_phim_safe = mysqli_real_escape_string($conn, $ma_phim);

// 1. Lấy thông tin phim
$sql_phim = "SELECT TenPhim FROM phim WHERE MaPhim = '$ma_phim_safe'";
$phim_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_phim));
$ten_phim = $phim_info['TenPhim'] ?? 'Không xác định';

// 2. Lấy tất cả suất chiếu cho phim này
$sql_suat = "SELECT R.TenRap, P.TenPhong, SC.MaSuatChieu, SC.ThoiGianBatDau 
             FROM suatchieu SC 
             JOIN phongchieu P ON SC.MaPhong = P.MaPhong
             JOIN rapchieu R ON P.MaRap = R.MaRap
             WHERE SC.MaPhim = '$ma_phim_safe'
             ORDER BY R.TenRap, SC.ThoiGianBatDau";
$result_suat = mysqli_query($conn, $sql_suat);
?>

<!DOCTYPE html>
<html>
<head>
    <title>1. Chọn Suất Chiếu - <?php echo $ten_phim; ?></title>
    <style>
        .showtimes-table { width: 80%; border-collapse: collapse; margin-top: 20px; }
        .showtimes-table th, .showtimes-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .showtimes-table th { background-color: #f2f2f2; }
        .btn-select { background-color: #e50914; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; display: inline-block; }
    </style>
</head>
<body>
    <h1>🎬 Đặt Vé: <?php echo htmlspecialchars($ten_phim); ?></h1>
    <h2>Chọn Rạp và Thời Gian Chiếu</h2>

    <?php if (mysqli_num_rows($result_suat) == 0): ?>
        <p>Hiện không có suất chiếu nào cho phim này.</p>
    <?php else: ?>
        <table class="showtimes-table">
            <thead>
                <tr>
                    <th>Rạp Chiếu</th>
                    <th>Phòng</th>
                    <th>Thời Gian Bắt Đầu</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($suat = mysqli_fetch_assoc($result_suat)): ?>
                    <?php
                        $thoi_gian = date('H:i d/m/Y', strtotime($suat['ThoiGianBatDau']));
                        $url_ghe = "chon_ghe.php?MaSuatChieu=" . urlencode($suat['MaSuatChieu']) . "&MaPhim=" . urlencode($ma_phim);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($suat['TenRap']); ?></td>
                        <td><?php echo htmlspecialchars($suat['TenPhong']); ?></td>
                        <td><?php echo $thoi_gian; ?></td>
                        <td>
                            <a href="<?php echo $url_ghe; ?>" class="btn-select">Chọn Ghế</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
<?php mysqli_close($conn); ?>