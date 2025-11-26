<?php
// admin/modules/users/list.php

$title = 'Quản lí phim';

// Lấy dữ liệu từ DB... (giả sử đã có $userList)

ob_start(); // Bắt đầu gom nội dung HTML vào buffer
?>

<h2>Đây là trang quản lí phim</h2>

<?php
$content = ob_get_clean(); // lấy nội dung buffer đưa vào $content

include __DIR__ . '/../../layouts/master.php';
?>