<!DOCTYPE html>
<html>
<head>
    <title>Đặt Vé Thành Công</title>
</head>
<body>
    <h1>🎉 Đặt Vé Thành Công!</h1>
    <p style="color: green; font-weight: bold; font-size: 1.2em;">
        <?php echo htmlspecialchars($_GET['msg'] ?? 'Vé của bạn đã được đặt.'); ?>
    </p>
    <p><a href="../index.php">Quay về trang chủ</a></p>
</body>
</html>