<?php
// admin/modules/movie/delete.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?module=movie&action=list');
    exit;
}

if (!isset($conn)) {
    require_once __DIR__ . '/../../config/database.php'; // nếu bạn dùng file kết nối riêng, sửa path cho đúng
}

$MaPhim = $_POST['MaPhim'] ?? '';
$MaPhim = trim($MaPhim);

if ($MaPhim === '') {
    header('Location: index.php?module=movie&action=list');
    exit;
}

$MaPhim_sql = mysqli_real_escape_string($conn, $MaPhim);

// (Tuỳ chọn) Lấy thông tin phim trước khi xóa để xóa luôn poster local nếu có
$sql_movie = "SELECT Hinhanh FROM phim WHERE MaPhim = '$MaPhim_sql' LIMIT 1";
$res_movie = mysqli_query($conn, $sql_movie);
$posterFile = null;

if ($res_movie && mysqli_num_rows($res_movie) > 0) {
    $row_movie  = mysqli_fetch_assoc($res_movie);
    $posterFile = trim($row_movie['Hinhanh'] ?? '');
}

// Bật chế độ ném exception cho mysqli (cho dễ debug / rollback)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    mysqli_begin_transaction($conn);

    // 1. Xóa tất cả vé đặt (datve) liên quan đến suất chiếu của phim này
    $sql_delete_datve = "
        DELETE dv
        FROM datve dv
        JOIN suatchieu s ON dv.MaSuatChieu = s.MaSuatChieu
        WHERE s.MaPhim = '$MaPhim_sql'
    ";
    mysqli_query($conn, $sql_delete_datve);

    // 2. Xóa tất cả suất chiếu của phim này
    $sql_delete_suatchieu = "
        DELETE FROM suatchieu
        WHERE MaPhim = '$MaPhim_sql'
    ";
    mysqli_query($conn, $sql_delete_suatchieu);

    // 3. Xóa phim
    $sql_delete_movie = "
        DELETE FROM phim
        WHERE MaPhim = '$MaPhim_sql'
        LIMIT 1
    ";
    mysqli_query($conn, $sql_delete_movie);

    mysqli_commit($conn);

    // 4. (Tuỳ chọn) Xóa file poster local nếu có
    if ($posterFile !== null && $posterFile !== '') {
        // Nếu là link online (http/https) thì bỏ qua
        if (!preg_match('~^https?://~', $posterFile)) {

            // Từ admin/modules/movie/delete.php lùi 3 cấp lên project root
            $posterPath = __DIR__ . '/../../../uploads/movies/' . $posterFile;

            // Kiểm tra tồn tại rồi mới xóa
            if (file_exists($posterPath)) {
                @unlink($posterPath);
            }
        }
    }
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    // Ở môi trường thực tế nên ghi log, trong bài tập có thể tạm redirect
    // hoặc debug bằng var_dump($e->getMessage());
}

// Quay lại danh sách phim
header('Location: index.php?module=movie&action=list');
exit;
