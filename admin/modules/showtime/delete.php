<?php
// admin/modules/showtime/delete.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?module=showtime&action=index');
    exit;
}

if (
    !isset($_POST['MaRap']) ||
    !isset($_POST['MaPhim']) ||
    !isset($_POST['MaSuatChieu']) ||
    $_POST['MaRap'] === '' ||
    $_POST['MaPhim'] === '' ||
    $_POST['MaSuatChieu'] === ''
) {
    header('Location: index.php?module=showtime&action=index');
    exit;
}

$MaRap       = mysqli_real_escape_string($conn, $_POST['MaRap']);
$MaPhim      = mysqli_real_escape_string($conn, $_POST['MaPhim']);
$MaSuatChieu = mysqli_real_escape_string($conn, $_POST['MaSuatChieu']);

// Nếu có bảng datve liên kết đến suatchieu thì bình thường cần kiểm tra/ xử lý trước khi xóa.
// Ở đây tạm thời xóa trực tiếp 1 suất chiếu.
$sql = "DELETE FROM suatchieu WHERE MaSuatChieu = '$MaSuatChieu' AND MaPhim = '$MaPhim' LIMIT 1";
mysqli_query($conn, $sql);

header('Location: index.php?module=showtime&action=list&MaRap=' . urlencode($MaRap) . '&MaPhim=' . urlencode($MaPhim));
exit;
