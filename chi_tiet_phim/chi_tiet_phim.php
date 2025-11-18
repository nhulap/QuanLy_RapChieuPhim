<?php
// BẮT BUỘC: Khởi tạo session để kiểm tra trạng thái đăng nhập
session_start(); 
require "../Connection.php"; // Kết nối CSDL

// =======================================================
// ⭐ PHẦN SỬA LẠI: GÁN CỨNG MÃ KHÁCH HÀNG ĐỂ THỬ NGHIỆM
// =======================================================
$ma_khach_hang_set_cung = 'KH1001'; // <-- ĐIỀN MÃ KHÁCH HÀNG CỦA BẠN VÀO ĐÂY
$_SESSION['MaKhachHang'] = $ma_khach_hang_set_cung; 
// =======================================================


// Kiểm tra xem MaPhim có được truyền qua URL không
if (!isset($_GET['MaPhim']) || empty($_GET['MaPhim'])) {
    // Giả định nếu không có MaPhim, chuyển hướng về trang chủ
    header("Location: index.php"); 
    exit();
}

// Lấy MaPhim từ URL và làm sạch dữ liệu
$ma_phim = mysqli_real_escape_string($conn, $_GET['MaPhim']);

// Lấy MaKhachHang từ Session (ĐÃ ĐƯỢC SET CỨNG ở trên)
$ma_khach_hang = $_SESSION['MaKhachHang'] ?? 'GUEST';

// Truy vấn lấy TẤT CẢ thông tin chi tiết của phim
$sql_detail = "SELECT * FROM phim WHERE MaPhim = '$ma_phim'";
$result_detail = mysqli_query($conn, $sql_detail);

// Kiểm tra kết quả
if (mysqli_num_rows($result_detail) == 0) {
    $phim = null;
    $error_msg = "Không tìm thấy phim có mã: " . htmlspecialchars($ma_phim);
} else {
    $phim = mysqli_fetch_assoc($result_detail);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../stylelap.css"> 
    <title>Chi tiết Phim: <?php echo $phim ? htmlspecialchars($phim['TenPhim']) : 'Không tìm thấy'; ?></title>
    <style>
        /* CSS Tùy chỉnh */
        .movie-detail-container { display: flex; gap: 30px; padding: 20px; border: 1px solid #ccc; background-color: #f9f9f9; }
        .detail-poster { flex-shrink: 0; width: 300px; height: 450px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .detail-info h1 { color: #E50914; margin-top: 0; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        .detail-info p { line-height: 1.6; margin-bottom: 15px; }
        .detail-info strong { display: inline-block; width: 120px; color: #333; }
        .description { margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 15px; }
        .btn-buy-detail, .btn-login-prompt {
            display: inline-block; padding: 10px 25px; background-color: #E50914; color: white; text-decoration: none; 
            border-radius: 5px; margin-top: 20px; font-size: 1.1em; transition: background-color 0.3s;
        }
        .btn-buy-detail:hover, .btn-login-prompt:hover { background-color: #f40a17; }
        .btn-login-prompt { background-color: #337ab7; } /* Màu khác cho nút đăng nhập */
        .debug-info { color: #007bff; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header"><div class="logo">CGV CINEMAS</div></div>
        <div class="menu">
            <ul>
                <li><a href="../index.php">Trang chủ</a></li> 
            </ul>
        </div>
        
        <div class="main">
            <h2>🎥 Chi Tiết Phim</h2>
            
            <?php if ($phim): ?>

            <p class="debug-info">Đang Đăng Nhập với Mã Khách Hàng: <?php echo htmlspecialchars($ma_khach_hang); ?></p> 
            
            <div class="movie-detail-container">
                <img src="<?php echo htmlspecialchars($phim['Hinhanh']); ?>" 
                     alt="<?php echo htmlspecialchars($phim['TenPhim']); ?>" 
                     class="detail-poster">
                
                <div class="detail-info">
                    <h1><?php echo htmlspecialchars($phim['TenPhim']); ?></h1>
                    
                    <p><strong>Thời Lượng:</strong> <?php echo htmlspecialchars($phim['ThoiLuong']); ?> phút</p>
                    <p><strong>Thể Loại:</strong> <?php echo htmlspecialchars($phim['TheLoai']); ?></p>
                    <p><strong>Đạo Diễn:</strong> <?php echo htmlspecialchars($phim['DaoDien']); ?></p>
                    <p><strong>Diễn Viên:</strong> <?php echo htmlspecialchars($phim['DienVien']); ?></p>
                    <p>
                        <strong>Khởi Chiếu:</strong> 
                        <?php 
                            $date = new DateTime($phim['NgayKhoiChieu']);
                            echo $date->format('d/m/Y');
                        ?>
                    </p>
                    <p><strong>Ngôn Ngữ:</strong> <?php echo htmlspecialchars($phim['NgonNgu']); ?></p>
                    
                    <a href="chon_rap_suat.php?MaPhim=<?php echo urlencode($ma_phim); ?>" class="btn-buy-detail">
                        MUA VÉ XEM PHIM
                    </a>

                    <div class="description">
                        <h3>Tóm Tắt Nội Dung</h3>
                        <p><?php echo nl2br(htmlspecialchars($phim['MoTa'])); ?></p>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
                <div style="text-align: center; color: red; padding: 50px;"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
        </div>
        
        <div class="footer">
            <p>&copy; 2025 CJ CGV VIETNAM. All rights reserved.</p>
        </div>
    </div>

<?php
if (isset($conn)) {
    mysqli_close($conn); 
}
?>
</body>
</html>