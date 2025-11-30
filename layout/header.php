<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $css_path ?? '../stylelap.css'; ?>"> 
    
    <title><?php echo $page_title ?? "CGV - Rạp Chiếu Phim"; ?></title>
</head>
<body>
    <div class="wrapper">
        
        <div class="header">
            <div class="logo">CGV CINEMAS</div>
        </div>
        
        <div class="menu">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/index.php">Trang chủ</a></li>
                <li><a href="<?php echo BASE_URL; ?>/phim/phimdangchieu.php">Phim</a></li>
                <li><a href="<?php echo BASE_URL; ?>/datphong/datphong.php">Đặt phòng chiếu</a></li>
                <!-- <li><a href="#">Rạp/Giá vé</a></li> -->

                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <li><a href="/profile/profile.php" style="font-weight: bold; color: yellow;">
                        Chào, <?php echo htmlspecialchars($_SESSION['user']); ?>
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>/Login&Register/logout.php" style="color: #ffcccc;">Đăng xuất</a></li>
                <?php else: ?>
                     <li><a href="<?php echo BASE_URL; ?>/Login&Register/Login.php">Đăng nhập</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/Login&Register/Register.php">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="main">