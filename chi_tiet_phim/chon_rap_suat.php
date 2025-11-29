<?php
session_start();
require "../Connection.php"; 

$ma_phim = $_GET['MaPhim'] ?? die("Thiếu Mã Phim.");
$ma_phim_safe = mysqli_real_escape_string($conn, $ma_phim);

// Lấy thông tin phim
$sql_phim = "SELECT TenPhim FROM phim WHERE MaPhim = '$ma_phim_safe'";
$phim_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_phim));
$ten_phim = $phim_info['TenPhim'] ?? 'Không xác định';

// Lấy tất cả suất chiếu cho phim này
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Suất Chiếu - <?php echo htmlspecialchars($ten_phim); ?></title>
    <style>
       /* ======================================================= */
/* CHUNG CHO TRANG CHỌN SUẤT CHIẾU */
/* ======================================================= */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
}

.container {
    width: 90%;
    max-width: 1000px;
    margin: 40px auto;
    background-color: #fff;
    padding: 30px 25px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    min-height: 400px;
    margin-top: 150px;
}

h1, h2 {
    color: #d11e3b;
    text-align: center;
    margin-bottom: 20px;
}

.showtimes-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.showtimes-table th, .showtimes-table td {
    border: 1px solid #ddd;
    padding: 16px 12px;
    text-align: left;
}

.showtimes-table th {
    background-color: #d11e3b;
    color: white;
    font-weight: bold;
}

.showtimes-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.showtimes-table tr:hover {
    background-color: #ffe5e5;
}

.btn-select {
    background-color: #d11e3b;
    color: white;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: background-color 0.3s, transform 0.2s;
}

.btn-select:hover {
    background-color: #a3182d;
    transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 25px 15px;
        min-height: 550px;
    }
    .showtimes-table th, .showtimes-table td {
        padding: 12px 8px;
        font-size: 14px;
    }
    h1, h2 {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .container {
        width: 100%;
        padding: 20px 10px;
        min-height: 450px;
    }
    .showtimes-table th, .showtimes-table td {
        padding: 10px 5px;
        font-size: 12px;
    }
    h1, h2 {
        font-size: 18px;
    }
    .btn-select {
        padding: 6px 12px;
        font-size: 12px;
    }
}

    </style>
</head>
<body>
    <div class="container">
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
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
