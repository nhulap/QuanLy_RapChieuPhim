<?php
// ⭐ BƯỚC 1: BẮT ĐẦU SESSION VÀ KẾT NỐI CSDL
session_start();

// Kết nối CSDL (Giả sử Connection.php nằm cùng cấp)
require "Connection.php";

// Định nghĩa biến để truyền vào header
$page_title = "Trang chủ - CGV";
$css_path = 'stylelap.css'; // Đường dẫn đến CSS từ index.php

// Nhúng Header (Mở thẻ HTML, header, menu, và div.main)
require_once 'layout/header.php'; 

// Các biến cần thiết cho nội dung chính
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $css_path; ?>"> 
    
    <title><?php echo $page_title; ?></title>
</head>
<body>
    <div class="wrapper">
        
        
        <div class="main">

            <h2>🍿 Phim Đang Chiếu</h2>

            <div class="movie-grid">

                <?php
                $sql = "SELECT MaPhim, TenPhim, Hinhanh FROM phim WHERE NgayKhoiChieu <= '$today' ORDER BY NgayKhoiChieu DESC LIMIT 8";
                $result = mysqli_query($conn, $sql);
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        echo '<a href="chi_tiet_phim/chi_tiet_phim.php?MaPhim=' . urlencode($row["MaPhim"]) . '" class="movie-link">';
                        echo '<div class="movie-card">';
                        echo ' <img src="' . htmlspecialchars($row["Hinhanh"]) . '" alt="' . htmlspecialchars($row["TenPhim"]) . '" class="movie-poster">';
                        echo ' <div class="movie-info">';
                        echo '<h4>' . htmlspecialchars($row["TenPhim"]) . '</h4>'; 
                        echo ' <div class="btn-buy">Mua vé</div>';
                        echo '  </div>';
                        echo '</div>';
                        echo '</a>'; 
                    }
                } else {
                    echo "<p>Hiện tại không có phim nào đang chiếu.</p>";
                }
                ?>
            </div>

            <h2 style="margin-top: 40px;">🎬 Phim Sắp Chiếu</h2>

            <div class="movie-grid">
                <?php
                $sql_upcoming = "SELECT MaPhim, TenPhim, Hinhanh FROM phim WHERE NgayKhoiChieu > '$today' ORDER BY NgayKhoiChieu ASC LIMIT 4";
                $result_upcoming = mysqli_query($conn, $sql_upcoming);
                if (mysqli_num_rows($result_upcoming) > 0) {
                    while($row_upcoming = mysqli_fetch_assoc($result_upcoming)) {
                        echo '<a href="chi_tiet_phim/chi_tiet_phim.php?MaPhim=' . urlencode($row_upcoming["MaPhim"]) . '" class="movie-link">';
                        echo '<div class="movie-card coming-soon">';
                        echo '<img src="' . htmlspecialchars($row_upcoming["Hinhanh"]) . '" alt="' . htmlspecialchars($row_upcoming["TenPhim"]) . '" class="movie-poster">';
                        echo '<div class="movie-info">';
                        echo '<h4>' . htmlspecialchars($row_upcoming["TenPhim"]) . '</h4>';
                        echo '<div class="btn-buy disabled">Sắp Chiếu</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</a>';
                    }
                } else {
                    echo "<p>Hiện tại không có phim nào sắp chiếu.</p>";
                }
                ?>
            </div>
        </div>
        
    </div>
    
    <?php
    // Đóng kết nối CSDL
    if (isset($conn)) { mysqli_close($conn); }
    ?>

</body>
</html>