<?php
// admin/modules/showtime/create.php

$title = 'Thêm suất chiếu';

if (!isset($_GET['MaRap']) || !isset($_GET['MaPhim'])) {
    die('Thiếu tham số.');
}

$MaRap  = mysqli_real_escape_string($conn, $_GET['MaRap']);
$MaPhim = mysqli_real_escape_string($conn, $_GET['MaPhim']);

// Thông tin rạp
$sql_rap = "SELECT * FROM rapchieu WHERE MaRap = '$MaRap' LIMIT 1";
$res_rap = mysqli_query($conn, $sql_rap);
if (!$res_rap || mysqli_num_rows($res_rap) == 0) {
    die('Không tìm thấy rạp.');
}
$rap = mysqli_fetch_assoc($res_rap);

// Thông tin phim
$sql_phim = "SELECT * FROM phim WHERE MaPhim = '$MaPhim' LIMIT 1";
$res_phim = mysqli_query($conn, $sql_phim);
if (!$res_phim || mysqli_num_rows($res_phim) == 0) {
    die('Không tìm thấy phim.');
}
$phim = mysqli_fetch_assoc($res_phim);

// Phòng chiếu thuộc rạp
$sql_phong = "SELECT MaPhong, TenPhong, LoaiPhong FROM phongchieu WHERE MaRap = '$MaRap' ORDER BY TenPhong";
$res_phong = mysqli_query($conn, $sql_phong);

$errors = [];
$success = '';
$old = [
    'MaPhong'        => '',
    'ThoiGianBatDau' => '',
    'GiaVeCoBan'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['MaPhong']        = trim($_POST['MaPhong'] ?? '');
    $old['ThoiGianBatDau'] = trim($_POST['ThoiGianBatDau'] ?? '');
    $old['GiaVeCoBan']     = trim($_POST['GiaVeCoBan'] ?? '');

    if ($old['MaPhong'] === '') {
        $errors['MaPhong'] = 'Vui lòng chọn phòng chiếu.';
    }

    if ($old['ThoiGianBatDau'] === '') {
        $errors['ThoiGianBatDau'] = 'Vui lòng chọn thời gian bắt đầu.';
    }

    if ($old['GiaVeCoBan'] === '') {
        $errors['GiaVeCoBan'] = 'Vui lòng nhập giá vé.';
    } elseif (!is_numeric($old['GiaVeCoBan']) || $old['GiaVeCoBan'] <= 0) {
        $errors['GiaVeCoBan'] = 'Giá vé phải là số dương.';
    }

    if (empty($errors)) {
        // Convert datetime-local → datetime MySQL
        $timeRaw = $old['ThoiGianBatDau']; // Y-m-dTH:i
        $timestamp = strtotime($timeRaw);
        if ($timestamp === false) {
            $errors['ThoiGianBatDau'] = 'Định dạng thời gian không hợp lệ.';
        } else {
            $ThoiGianBatDau_sql = date('Y-m-d H:i:s', $timestamp);
        }
    }

    if (empty($errors)) {
        // Tạo MaSuatChieu mới
        $sql_max = "SELECT MAX(CAST(SUBSTRING(MaSuatChieu, 2) AS UNSIGNED)) AS max_id FROM suatchieu";
        $res_max = mysqli_query($conn, $sql_max);
        $row_max = mysqli_fetch_assoc($res_max);
        $max_id = (int)($row_max['max_id'] ?? 0);
        $new_id_num = $max_id + 1;
        $MaSuatChieu_new = 'S' . str_pad($new_id_num, 3, '0', STR_PAD_LEFT);

        $MaPhong_sql    = mysqli_real_escape_string($conn, $old['MaPhong']);
        $GiaVe_sql      = (float)$old['GiaVeCoBan'];

        $sql_insert = "
            INSERT INTO suatchieu (MaSuatChieu, MaPhim, MaPhong, ThoiGianBatDau, GiaVeCoBan)
            VALUES ('$MaSuatChieu_new', '$MaPhim', '$MaPhong_sql', '$ThoiGianBatDau_sql', $GiaVe_sql)
        ";

        if (mysqli_query($conn, $sql_insert)) {
            $success = 'Thêm suất chiếu thành công.';
            $old = ['MaPhong' => '', 'ThoiGianBatDau' => '', 'GiaVeCoBan' => ''];
        } else {
            $errors['global'] = 'Lỗi khi thêm suất chiếu: ' . mysqli_error($conn);
        }
    }
}

ob_start();
?>

<div class="card cgv-card shadow-sm p-4 mb-4">
    <div class="card-header cgv-card-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-plus-circle me-2"></i> Thêm suất chiếu
                </h4>
                <div class="small text-light">
                    Phim: <?= htmlspecialchars($phim['TenPhim']) ?> – Rạp: <?= htmlspecialchars($rap['TenRap']) ?>
                </div>
            </div>
            <a href="index.php?module=showtime&action=list&MaRap=<?= htmlspecialchars($MaRap) ?>&MaPhim=<?= htmlspecialchars($MaPhim) ?>"
                class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>

    <?php if (!empty($errors['global'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['global']) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" class="cgv-form">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label text-light">Phòng chiếu</label>
                <select name="MaPhong"
                    class="form-select cgv-input <?= isset($errors['MaPhong']) ? 'is-invalid' : '' ?>">
                    <option value="">-- Chọn phòng --</option>
                    <?php mysqli_data_seek($res_phong, 0); ?>
                    <?php while ($p = mysqli_fetch_assoc($res_phong)): ?>
                        <option value="<?= htmlspecialchars($p['MaPhong']) ?>"
                            <?= $old['MaPhong'] === $p['MaPhong'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['TenPhong']) ?> (<?= htmlspecialchars($p['LoaiPhong']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if (isset($errors['MaPhong'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['MaPhong']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label text-light">Thời gian bắt đầu</label>
                <input type="datetime-local" name="ThoiGianBatDau"
                    class="form-control cgv-input <?= isset($errors['ThoiGianBatDau']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['ThoiGianBatDau']) ?>">
                <?php if (isset($errors['ThoiGianBatDau'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['ThoiGianBatDau']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label text-light">Giá vé cơ bản (đ)</label>
                <input type="number" name="GiaVeCoBan"
                    class="form-control cgv-input <?= isset($errors['GiaVeCoBan']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['GiaVeCoBan']) ?>">
                <?php if (isset($errors['GiaVeCoBan'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['GiaVeCoBan']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3 text-end">
            <button type="submit" class="btn cgv-btn-primary">
                <i class="fas fa-save"></i> Lưu suất chiếu
            </button>
        </div>

    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/master.php';
