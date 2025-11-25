<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylelap.css">
    <title>CGV - Rạp Chiếu Phim (Mockup)</title>
</head>

<body>
    <?php
    // ==========================================================
    // KẾT NỐI CƠ SỞ DỮ LIỆU
    // Sử dụng require để nhúng tệp kết nối (Connection.php)
    // Tệp Connection.php phải chứa biến $conn
    // ==========================================================
    require "Connection.php";
    session_start();

    // Tùy chọn: Thêm dòng này để kiểm tra kết nối ngay sau khi require
    // if (isset($conn) && $conn) {
    //     echo "<h3 style='text-align: center; color: green;'>✅ Kết nối CSDL thành công!</h3>";
    // }
    // ==========================================================
    ?>
    <div class="wrapper">

        <div class="header">
            <div class="logo">CGV CINEMAS</div>
        </div>

        <div class="menu">
            <ul>
                <li><a href="#">Trang chủ</a></li>
                <li><a href="#">Phim</a></li>
                <li><a href="#">Rạp/Giá vé</a></li>
                <li><a href="#">Thành viên</a></li>
                <li><a href="#">Tuyển dụng</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li>
                        <?= $_SESSION['user']; ?>
                    </li>
                    <li>
                        <a href="Login&Register/logout.php">Đăng xuất</a>
                    </li>
                    
                <?php else: ?>
                    <li><a href="Login&Register/Register.php">Đăng ký</a></li>
                    <li><a href="Login&Register/Login.php">Đăng nhập</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="main">

            <h2>🍿 Phim Đang Chiếu</h2>

            <div class="movie-grid">

                <?php
                // Khởi tạo ngày hiện tại
                $today = date('Y-m-d');

                // 1. Truy vấn Phim Đang Chiếu
                // 1. Truy vấn Phim Đang Chiếu (THÊM MaPhim vào SELECT)
                $sql = "SELECT MaPhim, TenPhim, Hinhanh FROM phim WHERE NgayKhoiChieu <= '$today' ORDER BY NgayKhoiChieu DESC LIMIT 8";
                $result = mysqli_query($conn, $sql);

                // 2. Kiểm tra và Lặp qua dữ liệu
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // In mã HTML cho mỗi Card Phim
                        // THÊM THẺ <a> bao quanh .movie-card VÀ TRUYỀN MaPhim
                        echo '<a href="chi_tiet_phim/chi_tiet_phim.php?MaPhim=' . urlencode($row["MaPhim"]) . '" class="movie-link">';
                        echo '<div class="movie-card">';
                        echo '    <img src="' . htmlspecialchars($row["Hinhanh"]) . '" alt="' . htmlspecialchars($row["TenPhim"]) . '" class="movie-poster">';
                        echo '    <div class="movie-info">';
                        echo '        <h4>' . htmlspecialchars($row["TenPhim"]) . '</h4>';
                        echo '        <div class="btn-buy">Mua vé</div>'; // Thay thẻ <a> bằng <div> để thẻ <a> lớn bao quanh
                        echo '    </div>';
                        echo '</div>';
                        echo '</a>'; // Đóng thẻ <a>
                    }
                } else {
                    echo "<p>Hiện tại không có phim nào đang chiếu.</p>";
                }
                ?>
                ?>
            </div>

            <h2 style="margin-top: 40px;">🎬 Phim Sắp Chiếu</h2>

            <div class="movie-grid">
                <?php
                // 1. Truy vấn Phim Sắp Chiếu
                // 1. Truy vấn Phim Sắp Chiếu (THÊM MaPhim vào SELECT)
                $sql_upcoming = "SELECT MaPhim, TenPhim, Hinhanh FROM phim WHERE NgayKhoiChieu > '$today' ORDER BY NgayKhoiChieu ASC LIMIT 4";
                $result_upcoming = mysqli_query($conn, $sql_upcoming);

                // 2. Kiểm tra và Lặp qua dữ liệu
                if (mysqli_num_rows($result_upcoming) > 0) {
                    while ($row_upcoming = mysqli_fetch_assoc($result_upcoming)) {
                        // THÊM THẺ <a> bao quanh .movie-card VÀ TRUYỀN MaPhim
                        echo '<a href="/chi_tiet_phim/chi_tiet_phim.php?MaPhim=' . urlencode($row_upcoming["MaPhim"]) . '" class="movie-link">';
                        echo '<div class="movie-card coming-soon">';
                        echo '    <img src="' . htmlspecialchars($row_upcoming["Hinhanh"]) . '" alt="' . htmlspecialchars($row_upcoming["TenPhim"]) . '" class="movie-poster">';
                        echo '    <div class="movie-info">';
                        echo '        <h4>' . htmlspecialchars($row_upcoming["TenPhim"]) . '</h4>';
                        echo '        <div class="btn-buy disabled">Sắp Chiếu</div>';
                        echo '    </div>';
                        echo '</div>';
                        echo '</a>';
                    }
                } else {
                    echo "<p>Hiện tại không có phim nào sắp chiếu.</p>";
                }
                ?>
            </div>

        </div>

        <div class="footer">
            <p>&copy; 2025 CJ CGV VIETNAM. All rights reserved.</p>
            <p>Địa chỉ, Thông tin liên hệ...</p>
        </div>
    </div>

    <?php
    // Đóng kết nối: Đặt lệnh này sau khi đóng thẻ </html> để đảm bảo mọi thứ đã được gửi đi.
    if (isset($conn)) {
        mysqli_close($conn);
    }
    ?>
</body>

</html>