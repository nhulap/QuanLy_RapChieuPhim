<?php
session_start();
require "Connection.php";
require_once __DIR__ . '/config/config.php'; 
require_once __DIR__ . '/layout/header.php'; 
$page_title = "Trang chủ - CGV";
$css_path = 'stylelap_new.css';

require_once 'layout/header.php'; 

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $css_path; ?>">
    <title><?php echo $page_title; ?></title>

<style>
    .side-banner {
    position: fixed;
    top: 0px;
    width: 180px;
    height: 350px;
    z-index: 500;
    }

    .side-banner img {
    width: 100%;
    height: 950px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 0 8px rgba(0,0,0,0.4);
    }

    .side-banner.left {
        left: 10px;
    }


    .side-banner.right {
        right: 10px;
    }

    .carousel-container {
         width: 100%;
         max-width: 1100px;
         margin: 0 auto;
         position: relative;
         padding: 20px 0;
     }

     .carousel-title {
         text-align: center;
        font-size: 34px;
        font-weight: bold;
         margin-bottom: 20px;
         letter-spacing: 2px;
     }

     .carousel-track {
         display: flex;
         overflow: hidden;
        scroll-behavior: smooth;
     }

    .carousel-item {
     min-width: 100%;
        margin-right: 0;
         border-radius: 10px;
         overflow: hidden;
        transition: transform .3s;
    }

    .carousel-item img {
         width: 100%;
         height: 380px;
         object-fit: cover;
         border-radius: 10px;
     }

    .carousel-btn {
         position: absolute;
         top: 45%;
         transform: translateY(-50%);
          background: red;
         color: white;
         border-radius: 50%;
         width: 45px;
         height: 45px;
        border: none;
         cursor: pointer;
         font-size: 20px;
         display: flex;
         align-items: center;
         justify-content: center;
    }

     .carousel-btn.left { left: -20px; }
    .carousel-btn.right { right: -20px; }

    .carousel-btn:hover {
        background: #b30000;
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
if (isset($conn)) { mysqli_close($conn); }
?>

<script>
    const track = document.getElementById("carouselTrack");
    const items = document.querySelectorAll('.carousel-item');
    const containerWidth = document.querySelector('.carousel-container').offsetWidth;
    let currentIndex = 0;

    function scrollToSlide(index) {
        const scrollAmount = containerWidth * index;
        track.scrollTo({ left: scrollAmount, behavior: "smooth" });
        currentIndex = index % items.length;
    }

    function moveLeft() {
        scrollToSlide((currentIndex - 1 + items.length) % items.length);
    }

    function moveRight() {
        scrollToSlide((currentIndex + 1) % items.length);
    }

    setInterval(() => {
        moveRight();
    }, 5000);
</script>

</body>
</html>
