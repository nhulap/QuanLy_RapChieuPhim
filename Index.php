<?php
session_start();
require "Connection.php";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/layout/header.php';
$page_title = "Trang chủ - CGV";
// $css_path = 'stylelap_new.css';


$today = date('Y-m-d');

$theloai_set = [];
if (isset($conn)) {
    $sql_tl = "SELECT TheLoai FROM phim";
    $result_tl = mysqli_query($conn, $sql_tl);
    while($row_tl = mysqli_fetch_assoc($result_tl)){
        $list = explode(',', $row_tl['TheLoai']);
        foreach($list as $tl){
            $tl = trim($tl);
            if($tl != ''){
                $theloai_set[$tl] = true;
            }
        }
    }
}
$theloai_array = array_keys($theloai_set);
sort($theloai_array);

$selectedTheLoai = isset($_GET['theloai']) ? $_GET['theloai'] : '';

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

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
            height: 120px;
            width: 100%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 0;
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

        .carousel-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            margin: 0 auto 30px auto;
            border-radius: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .carousel-track {
            display: flex;
            scroll-snap-type: x mandatory;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .carousel-track::-webkit-scrollbar {
            display: none;
        }
        .carousel-item {
            min-width: 100%;
            height: auto;
            scroll-snap-align: start;
            display: block;
            text-decoration: none;
            position: relative;
            z-index: 1;
        }
        .carousel-item img {
            width: 100%;
            display: block;
            object-fit: cover;
        }
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            z-index: 10;
            font-size: 30px;
            line-height: 1;
            transition: background 0.3s;
            border-radius: 50%;
        }
        .carousel-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        .carousel-btn.left {
            left: 10px;
        }
        .carousel-btn.right {
            right: 10px;
        }

        h2 {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 30px 0 15px 0; 
        }
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr); 
            gap: 15px; 
            padding: 10px 0; 
        }
        
        @media (max-width: 1300px) {
             .movie-grid { grid-template-columns: repeat(5, 1fr); }
        }
        @media (max-width: 1050px) {
             .movie-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 850px) {
            .movie-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 600px) {
            .movie-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .movie-link {
            text-decoration: none;
            color: inherit;
        }
        .movie-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            background: #fff;
            cursor: pointer;
        }
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .movie-poster {
            width: 100%;
            aspect-ratio: 3/4;
            display: block;
            object-fit: cover;
        }
        .movie-info {
            padding: 10px 5px; 
            text-align: center;
        }
        .movie-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px; 
            color: #333;
            height: 35px; 
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.2;
            text-transform: uppercase;
            font-weight: bold;
        }
        .btn-buy {
            display: inline-block;
            background-color: #d11e3b;
            color: white;
            padding: 6px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
            transition: 0.2s;
            text-transform: uppercase;
            font-size: 12px; 
        }
        .btn-buy:hover {
            background-color: #a3182d;
        }
        .btn-buy.disabled {
            background-color: #aaa;
            cursor: default;
        }
        .btn-buy.disabled:hover {
            background-color: #aaa;
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

    </style>
</head>

<body>

<div class="wrapper">
    <div class="content-container">
        <div class="main">

            <div class="carousel-container">
                <button class="carousel-btn left" onclick="moveLeft()">&#8249;</button>
                <div class="carousel-track" id="carouselTrack">
                    <?php
                    $banners = array(
                        array('image' => 'image_rapchieuphim/banner.jpg', 'link' => '#'),
                        array('image' => 'image_rapchieuphim/banner2.jpg', 'link' => '#'),
                        array('image' => 'image_rapchieuphim/quankynam.jpg', 'link' => './chi_tiet_phim/chi_tiet_phim.php?MaPhim=MP0009')
                    );

                    foreach($banners as $banner){
                        echo '
                            <a href="'.$banner["link"].'" class="carousel-item">
                                <img src="'.$banner["image"].'" alt="Banner">
                            </a>
                        ';
                    }
                    ?>
                </div>
                <button class="carousel-btn right" onclick="moveRight()">&#8250;</button>
            </div>
            <div style="text-align: center; margin: 30px 0; font-size: 28px; font-weight: bold; letter-spacing: 2px;">
                MOVIE SELECTION
            </div>

            <h2>🍿 Phim Đang Chiếu</h2>

            <div class="movie-grid">
                <?php
                // Áp dụng bộ lọc thể loại nếu có
                $sql_dang_chieu = "SELECT MaPhim, TenPhim, Hinhanh FROM phim WHERE NgayKhoiChieu <= '$today'";
                if (!empty($selectedTheLoai)) {
                     $sql_dang_chieu .= " AND TheLoai LIKE '%$selectedTheLoai%'";
                }
                // CHỈNH SỬA: LIMIT 12 để hiển thị đủ 2 hàng 6 cột
                $sql_dang_chieu .= " ORDER BY NgayKhoiChieu DESC LIMIT 12"; 
                
                if (isset($conn)) {
                    $result = mysqli_query($conn, $sql_dang_chieu);
                    if ($result && mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            echo '<a href="chi_tiet_phim/chi_tiet_phim.php?MaPhim=' . urlencode($row["MaPhim"]) . '" class="movie-link">';
                            echo '<div class="movie-card">';
                            echo ' <img src="' . htmlspecialchars($row["Hinhanh"]) . '" alt="' . htmlspecialchars($row["TenPhim"]) . '" class="movie-poster">';
                            echo ' <div class="movie-info">';
                            echo '<h4>' . htmlspecialchars($row["TenPhim"]) . '</h4>';
                            echo ' <div class="btn-buy">Mua vé</div>';
                            echo '  </div>';
                            echo '</div>';
                            echo '</a>';
                        }
                    } else {
                        echo "<p>Hiện tại không có phim nào đang chiếu phù hợp với lựa chọn.</p>";
                    }
                } else {
                    echo "<p>Lỗi kết nối cơ sở dữ liệu.</p>";
                }
                ?>
            </div>
            <h2 style="margin-top: 40px;">🎬 Phim Sắp Chiếu</h2>

            <div class="movie-grid">
                <?php
                // Áp dụng bộ lọc thể loại nếu có
                $sql_upcoming = "SELECT MaPhim, TenPhim, Hinhanh FROM phim WHERE NgayKhoiChieu > '$today'";
                if (!empty($selectedTheLoai)) {
                     $sql_upcoming .= " AND TheLoai LIKE '%$selectedTheLoai%'";
                }

                $sql_upcoming .= " ORDER BY NgayKhoiChieu ASC LIMIT 6";
                
                if (isset($conn)) {
                    $result_upcoming = mysqli_query($conn, $sql_upcoming);
                    if ($result_upcoming && mysqli_num_rows($result_upcoming) > 0) {
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
                        echo "<p>Hiện tại không có phim nào sắp chiếu phù hợp với lựa chọn.</p>";
                    }
                } else {
                    echo "<p>Lỗi kết nối cơ sở dữ liệu.</p>";
                }
                ?>
            </div>
            </div>
    </div>
    <div class="footer">© 2025 Quản lý Rạp Phim. All rights reserved.</div>
    </div>

<?php
if (isset($conn)) { mysqli_close($conn); }
?>

<script>
    const track = document.getElementById("carouselTrack");
    const items = document.querySelectorAll('.carousel-item');
    
    const getContainerWidth = () => document.querySelector('.carousel-container').offsetWidth;
    let containerWidth = getContainerWidth();
    let currentIndex = 0;

    window.addEventListener('resize', () => {
        containerWidth = getContainerWidth();
        scrollToSlide(currentIndex, false); 
    });
    
    if (track && items.length > 0) {
        track.scrollLeft = 0; 

        function scrollToSlide(index, smooth = true) {
            if (!containerWidth) return;
            const targetIndex = index % items.length;
            const scrollAmount = containerWidth * targetIndex;
            track.scrollTo({ left: scrollAmount, behavior: smooth ? "smooth" : "auto" });
            currentIndex = targetIndex;
        }

        function moveLeft() {
            scrollToSlide((currentIndex - 1 + items.length) % items.length);
        }

        function moveRight() {
            scrollToSlide((currentIndex + 1) % items.length);
        }

        setInterval(() => {
            moveRight();
        }, 4000);
    }
</script>
</body>
</html>