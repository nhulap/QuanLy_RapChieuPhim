<?php
// admin/modules/phim/delete.php

include __DIR__ . '/../../../Connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?module=movie&action=list');
    exit;
}

if (!isset($_POST['MaPhim']) || $_POST['MaPhim'] === '') {
    header('Location: index.php?module=movie&action=list&msg=missing_id');
    exit;
}

$MaPhim = $_POST['MaPhim'];
$MaPhim_sql = mysqli_real_escape_string($conn, $MaPhim);

// Lấy thông tin phim để biết tên file poster
$sql_get = "SELECT Hinhanh FROM phim WHERE MaPhim = '$MaPhim_sql' LIMIT 1";
$result_get = mysqli_query($conn, $sql_get);

if (!$result_get || mysqli_num_rows($result_get) === 0) {
    header('Location: index.php?module=movie&action=list&msg=not_found');
    exit;
}

$movie = mysqli_fetch_assoc($result_get);
$posterName = $movie['Hinhanh'];

// Xóa bản ghi trong DB
$sql_delete = "DELETE FROM phim WHERE MaPhim = '$MaPhim_sql' LIMIT 1";
if (mysqli_query($conn, $sql_delete)) {

    // Xóa file poster cũ nếu là file local
    if (!empty($posterName) && !str_starts_with($posterName, 'http')) {
        $uploadPath = __DIR__ . '/../../../uploads/movies/';
        $filePath = $uploadPath . $posterName;

        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    header('Location: index.php?module=movie&action=list&msg=deleted');
    exit;
} else {
    header('Location: index.php?module=movie&action=list&msg=error');
    exit;
}
