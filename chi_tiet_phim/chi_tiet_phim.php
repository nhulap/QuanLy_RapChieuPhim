<?php
// BẮT BUỘC: Khởi tạo session để kiểm tra trạng thái đăng nhập
session_start(); 
require "../Connection.php"; // Kết nối CSDL

// GIẢ ĐỊNH: Config.php chỉ chứa các hằng số không gây lỗi session.
require_once __DIR__ . '/../config/config.php'; 
require_once __DIR__ . '/../layout/header.php'; 

// Kiểm tra xem MaPhim có được truyền qua URL không
if (!isset($_GET['MaPhim']) || empty($_GET['MaPhim'])) {
    header("Location: ../index.php"); 
    exit();
}

// Lấy dữ liệu
$ma_phim = mysqli_real_escape_string($conn, $_GET['MaPhim']);
$H = $_SESSION['user'] ?? 'Khách'; 
$is_logged_in = isset($_SESSION['user_id']);

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

$page_title = $phim ? "Chi tiết Phim: " . $phim['TenPhim'] : 'Không tìm thấy';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <style>
        /* --- CSS CHUNG TỪ INDEX.PHP (Đảm bảo đồng bộ giao diện) --- */
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .wrapper { width: 100%; margin: 0; padding: 0; max-width: none; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { height: 120px; width: 100%; background: #fff; display: flex; align-items: center; justify-content: center; padding: 10px 0; }
        .logo { font-size: 36px; font-weight: bold; color: #d11e3b; text-transform: uppercase; }
        .menu { height: 50px; width: 100%; background: #d11e3b; }
        .menu ul { list-style: none; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; height: 100%; }
        .menu li { padding: 0 15px; }
        .menu li a { text-decoration: none; color: #fff; font-weight: bold; font-size: 14px; text-transform: uppercase; padding: 15px 5px; transition: 0.3s; }
        .menu li a:hover { background-color: #a3182d; }
        .content-container { margin: 0 auto; padding: 0 20px; box-sizing: border-box; background: #fff; }
        .main { min-height: 400px; padding: 20px 0; box-sizing: border-box; background: #fff; }
        h2 { font-size: 28px; font-weight: bold; color: #333; margin: 30px 0 15px 0; }
        .footer { height: 100px; width: 100%; background: #222; color: #ccc; padding: 20px 0; text-align: center; font-size: 12px; box-sizing: border-box; }

        /* ⭐⭐ CSS CHI TIẾT PHIM (TRANG TRÍ LẠI) ⭐⭐ */
        .page-content { padding: 30px 0; background: #fff; }
        .movie-detail-container { 
            display: flex; 
            gap: 40px; 
            padding: 30px; 
            border: 1px solid #e0e0e0; 
            border-radius: 10px;
            background-color: #ffffff; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .detail-poster-wrapper {
            flex-shrink: 0; 
            width: 300px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-radius: 8px;
            overflow: hidden;
        }
        .detail-poster { 
            width: 100%; 
            height: 450px; 
            object-fit: cover; 
            display: block;
        }
        .detail-info { flex-grow: 1; }
        .detail-info h1 { 
            color: #d11e3b; 
            margin-top: 0; 
            border-bottom: 3px solid #f0f0f0; 
            padding-bottom: 15px; 
            font-size: 36px;
        }
        .detail-info p { line-height: 1.8; margin-bottom: 12px; font-size: 16px; }
        .detail-info strong { 
            display: inline-block; 
            width: 130px; 
            color: #111; 
            font-weight: bold;
        }
        .description { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px; }
        .description h3 { color: #333; font-size: 22px; margin-bottom: 10px; }
        .btn-buy-detail, .btn-login-prompt {
            display: inline-block; 
            padding: 12px 30px; 
            background-color: #d11e3b; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-top: 25px; 
            font-size: 1.2em; 
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-buy-detail:hover { background-color: #a3182d; }
        .btn-login-prompt { background-color: #337ab7; } 
        .btn-login-prompt:hover { background-color: #286090; } 
        .debug-info { color: #007bff; margin-bottom: 20px; font-weight: bold; font-style: italic; }
        
        /* Responsive Adjustment */
        @media (max-width: 800px) {
            .movie-detail-container { flex-direction: column; gap: 20px; padding: 20px; }
            .detail-poster-wrapper { width: 100%; height: auto; }
            .detail-poster { height: auto; max-height: 400px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    
    <div class="content-container">
        <div class="main">

            <div class="page-content">
                <h2>🎥 Chi Tiết Phim</h2>
                
                <?php if ($phim): ?>

                <p class="debug-info">Trạng thái: Khách hàng **<?php echo htmlspecialchars($H); ?>**</p> 
                
                <div class="movie-detail-container">
                    <div class="detail-poster-wrapper">
                        <img src="<?php echo htmlspecialchars($phim['Hinhanh']); ?>" 
                            alt="<?php echo htmlspecialchars($phim['TenPhim']); ?>" 
                            class="detail-poster">
                    </div>
                    
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
                        
                        <?php if (!$is_logged_in): ?>
                            <a href="../Login&Register/Login.php" class="btn-login-prompt">
                                ĐĂNG NHẬP ĐỂ MUA VÉ
                            </a>
                        <?php else: ?>
                            <a href="chon_rap_suat.php?MaPhim=<?php echo urlencode($ma_phim); ?>" class="btn-buy-detail">
                                MUA VÉ XEM PHIM
                            </a>
                        <?php endif; ?>
                        
                        <div class="description">
                            <h3>Tóm Tắt Nội Dung</h3>
                            <p><?php echo nl2br(htmlspecialchars($phim['MoTa'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                    <div style="text-align: center; color: red; padding: 50px; font-size: 18px;"><?php echo $error_msg; ?></div>
                <?php endif; ?>
            </div>

        </div> 
    </div>
    
    <div class="footer">© 2025 Quản lý Rạp Phim. All rights reserved.</div>
    
</div> 

<?php
if (isset($conn)) {
    mysqli_close($conn); 
}
?>
</body> 
</html>