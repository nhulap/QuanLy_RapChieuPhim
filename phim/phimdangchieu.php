<?php
session_start();
require "../Connection.php";

// Lấy ngày hiện tại
$today = date('Y-m-d');

// Lấy tất cả thể loại từ cột TheLoai, tách riêng theo dấu phẩy
$theloai_set = [];
$sql = "SELECT TheLoai FROM phim";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)){
    $list = explode(',', $row['TheLoai']); // tách theo dấu ,
    foreach($list as $tl){
        $tl = trim($tl); // loại bỏ khoảng trắng
        if($tl != ''){
            $theloai_set[$tl] = true; // key để loại bỏ trùng
        }
    }
}
$theloai_array = array_keys($theloai_set);
sort($theloai_array);

// Lấy thể loại được chọn từ GET
$selectedTheLoai = isset($_GET['theloai']) ? $_GET['theloai'] : '';

// Lấy danh sách phim Đang Chiếu (<= ngày hôm nay)
if($selectedTheLoai){
    $sql_dang_chieu = "SELECT * FROM phim WHERE NgayKhoiChieu <= '$today' AND TheLoai LIKE '%$selectedTheLoai%' ORDER BY NgayKhoiChieu DESC";
} else {
    $sql_dang_chieu = "SELECT * FROM phim WHERE NgayKhoiChieu <= '$today' ORDER BY NgayKhoiChieu DESC";
}
$result_dang_chieu = mysqli_query($conn, $sql_dang_chieu);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phim Đang Chiếu</title>
    <style>
        body {
             margin:0;
             padding:0; 
             font-family: Arial,sans-serif;
             background-color: #f0f0f0;
             }
        .wrapper {
             width:100%;
             margin:0;
             max-width:none; 
             background:#fff;
             box-shadow:0 0 10px rgba(0,0,0,0.1); 
             }
        .header {
             height:120px; 
             width:100%;
             background:#fff;
             display:flex;
             align-items:center; 
             justify-content:center;
             padding:10px 0; 
             }
        .logo {
             font-size:36px; 
             font-weight:bold;
             color:#d11e3b;
             text-transform:uppercase; 
             }
        .menu {
             height:50px; 
             width:100%; 
             background:#d11e3b; 
             }
        .menu ul { 
            list-style:none;
             margin:0; 
             max-width:none; 
             
             padding:0 20px; 
             display:flex;
             align-items:center;
             height:100%;
             justify-content: flex-start; 
             flex-wrap: nowrap;
             }
        .menu li {
             padding:0 15px;
             flex-shrink: 0;
             }
        .menu li a { 
             text-decoration:none;
             color:#fff;
             font-weight:bold;
             font-size:14px;
             text-transform:uppercase;
             padding:15px 5px;
             transition:0.3s; 
             }
        .menu li a:hover { 
             background-color:#a3182d;
          }
        .menu li form { 
             margin:0; 
         }
        .menu select {
             padding:5px;
             border-radius:3px;
             font-size:14px;
             border: none; 
             }

        .main {
             min-height:400px;
             max-width:1200px; 
             margin:0 auto; 
             padding:20px;
             box-sizing:border-box;
             background:#fff;
             }
        .section-title {
             font-size:28px; 
             font-weight:bold;
             color:#333;
             margin-bottom:15px;
             border-bottom:2px solid #d11e3b;
             padding-bottom:5px; 
             }
        .movie-grid { 
            display:grid;
             grid-template-columns:repeat(4,1fr);
             gap:20px;
             padding-top:10px;
             }
        .movie-card { 
             border:1px solid #ddd;
             border-radius:5px;
             overflow:hidden;
             box-shadow:0 2px 5px rgba(0,0,0,0.1);
             transition:transform 0.2s;
             background:#fff;
             cursor:pointer;
             text-decoration:none;
             color:inherit; 
             }
        .movie-card:hover {
             transform:translateY(-5px);
             box-shadow:0 8px 15px rgba(0,0,0,0.2);
             }
        .movie-poster {
             width:100%;
             aspect-ratio:3/4;
             display:block;
             object-fit:cover; 
             }
        .movie-info {
             padding:10px; 
             text-align:center;
             }
        .movie-info h4 {
             margin:0 0 5px 0;
             font-size:16px;
             color:#333;
             height:40px;
             overflow:hidden;
             text-overflow:ellipsis;
             display:-webkit-box;
             -webkit-line-clamp:2;
             -webkit-box-orient:vertical;
             line-height:1.2;
             text-transform:uppercase;
             font-weight:bold;
             }
        .movie-info p {
             margin:5px 0; 
             font-size:14px;
             color:#666; 
             }
        .movie-info .btn-buy {
             display:inline-block;
             background-color:#d11e3b;
             color:white;
             padding:8px 15px; 
             border-radius:3px;
             text-decoration:none; 
             font-weight:bold; 
             margin-top:5px;
             transition:0.2s;
             }
        .movie-info .btn-buy:hover { 
             background-color:#a3182d;
          }
        .footer { 
             height:100px;
             width:100%;
             background:#222; 
             color:#ccc; 
             padding:20px 0;
             text-align:center;
             font-size:12px; 
             box-sizing:border-box; 
             }
        .movie-info a {
             text-decoration:none;
             color:inherit; 
             
             }
        .movie-info a:hover { 
             color:#d11e3b; 
         }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header"><div class="logo">Quản Lý Rạp Phim</div></div>

    <div class="menu">
        <ul>
            <li><a href="../Index.php">Trang Chủ</a></li>
            <li><a href="./phimdangchieu.php">Phim Đang Chiếu</a></li>
            <li><a href="./phimsapchieu.php">Phim Sắp Chiếu</a></li> 
            <li>
                <form method="GET" action="">
                    <select name="theloai" onchange="this.form.submit()">
                        <option value="">-- Chọn thể loại --</option>
                        <?php
                        foreach($theloai_array as $tl){
                            $selected = ($tl == $selectedTheLoai) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($tl).'" '.$selected.'>'.htmlspecialchars($tl).'</option>';
                        }
                        ?>
                    </select>
                </form>
            </li>
        </ul>
    </div>

    <div class="main">
        <h2 class="section-title">Phim Đang Chiếu</h2>
        <div class="movie-grid">
        <?php
        if(mysqli_num_rows($result_dang_chieu) > 0){
            while($row = mysqli_fetch_assoc($result_dang_chieu)){
                $posterPath = htmlspecialchars($row['Hinhanh']);
                $link = '../chi_tiet_phim/chi_tiet_phim.php?MaPhim=' . urlencode($row["MaPhim"]);

                echo '<div class="movie-card">';
                echo '<a href="'.$link.'"><img src="'.$posterPath.'" alt="'.htmlspecialchars($row["TenPhim"]).'" class="movie-poster"></a>';
                echo '<div class="movie-info">';
                echo '<a href="'.$link.'"><h4>'.htmlspecialchars($row["TenPhim"]).'</h4></a>';
                echo '<p>Thể loại: '.htmlspecialchars($row["TheLoai"]).'</p>';
                echo '<p>Khởi chiếu: '.htmlspecialchars($row["NgayKhoiChieu"]).'</p>';
                echo '<a href="'.$link.'" class="btn-buy">Mua Vé</a>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<p>Hiện tại không có phim nào đang chiếu.</p>";
        }
        ?>
        </div>
    </div>

    <div class="footer">© 2025 Quản lý Rạp Phim. All rights reserved.</div>
</div>
</body>
</html>