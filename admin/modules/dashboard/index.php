<?php
// admin/modules/users/list.php

$title = 'Trang chủ';

// Lấy dữ liệu từ DB... (giả sử đã có $userList)

ob_start(); // Bắt đầu gom nội dung HTML vào buffer
?>

<h2>Hello, Dashboard</h2>

<?php
$content = ob_get_clean(); // lấy nội dung buffer đưa vào $content

include __DIR__ . '/../../layouts/master.php';
?>