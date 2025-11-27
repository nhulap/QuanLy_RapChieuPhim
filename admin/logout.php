<?php
session_start();

// Xóa toàn bộ biến session
$_SESSION = [];

// Xóa luôn session cookie (optional, nhưng nên có)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển về trang đăng nhập (sửa đúng đường dẫn Login của bạn)
require_once __DIR__ . '/../config/config.php'; // nếu cần để dùng USER_URL
header("Location: ../index.php");
exit();
