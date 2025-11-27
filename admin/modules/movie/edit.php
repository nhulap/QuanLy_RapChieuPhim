<?php
// admin/modules/phim/edit.php

$title = "Sửa phim";

$errors = [];
$success = "";
$movie  = null;

// Lấy mã phim từ URL
if (!isset($_GET['id']) || $_GET['id'] === '') {
    die("Thiếu mã phim cần sửa.");
}

$MaPhimParam = $_GET['id'];
$MaPhimParam_sql = mysqli_real_escape_string($conn, $MaPhimParam);

// Lấy dữ liệu phim từ CSDL
$sql_get = "SELECT * FROM phim WHERE MaPhim = '$MaPhimParam_sql' LIMIT 1";
$result_get = mysqli_query($conn, $sql_get);

if (!$result_get || mysqli_num_rows($result_get) === 0) {
    die("Không tìm thấy phim với mã: " . htmlspecialchars($MaPhimParam));
}

$movie = mysqli_fetch_assoc($result_get);

// Mảng để đổ lại lên form
$old = [
    'MaPhim'        => $movie['MaPhim'],
    'TenPhim'       => $movie['TenPhim'],
    'ThoiLuong'     => $movie['ThoiLuong'],
    'TheLoai'       => $movie['TheLoai'],
    'DaoDien'       => $movie['DaoDien'],
    'DienVien'      => $movie['DienVien'],
    'NgayKhoiChieu' => $movie['NgayKhoiChieu'],
    'NgonNgu'       => $movie['NgonNgu'],
    'MoTa'          => $movie['MoTa'],
];

$currentPoster = $movie['Hinhanh']; // tên file ảnh hiện tại

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lấy dữ liệu từ form
    $old['TenPhim']       = trim($_POST['TenPhim'] ?? '');
    $old['ThoiLuong']     = trim($_POST['ThoiLuong'] ?? '');
    $old['TheLoai']       = trim($_POST['TheLoai'] ?? '');
    $old['DaoDien']       = trim($_POST['DaoDien'] ?? '');
    $old['DienVien']      = trim($_POST['DienVien'] ?? '');
    $old['NgayKhoiChieu'] = trim($_POST['NgayKhoiChieu'] ?? '');
    $old['NgonNgu']       = trim($_POST['NgonNgu'] ?? '');
    $old['MoTa']          = trim($_POST['MoTa'] ?? '');

    // ========== VALIDATE ĐƠN GIẢN ==========

    if ($old['TenPhim'] === '') {
        $errors['TenPhim'] = "Tên phim không được để trống.";
    }

    if ($old['ThoiLuong'] === '') {
        $errors['ThoiLuong'] = "Thời lượng không được để trống.";
    } elseif (!ctype_digit($old['ThoiLuong'])) {
        $errors['ThoiLuong'] = "Thời lượng phải là số.";
    }

    if ($old['TheLoai'] === '') {
        $errors['TheLoai'] = "Thể loại không được để trống.";
    }

    if ($old['DaoDien'] === '') {
        $errors['DaoDien'] = "Đạo diễn không được để trống.";
    }

    if ($old['DienVien'] === '') {
        $errors['DienVien'] = "Diễn viên không được để trống.";
    }

    if ($old['NgayKhoiChieu'] === '') {
        $errors['NgayKhoiChieu'] = "Ngày khởi chiếu không được để trống.";
    }

    if ($old['NgonNgu'] === '') {
        $errors['NgonNgu'] = "Ngôn ngữ không được để trống.";
    }

    if ($old['MoTa'] !== '' && mb_strlen($old['MoTa']) > 2000) {
        $errors['MoTa'] = "Mô tả tối đa 2000 ký tự.";
    }

    // ẢNH POSTER (không bắt buộc, nếu không chọn thì giữ ảnh cũ)
    $imageName = $currentPoster;

    if (!empty($_FILES['Hinhanh']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['Hinhanh']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors['Hinhanh'] = "Ảnh không hợp lệ! Chỉ nhận jpg, jpeg, png, gif, webp.";
        } elseif ($_FILES['Hinhanh']['size'] > 2 * 1024 * 1024) {
            $errors['Hinhanh'] = "Kích thước ảnh tối đa 2MB.";
        } else {
            $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;
        }
    }

    // Nếu không có lỗi → xử lý cập nhật
    if (empty($errors)) {

        // Nếu có ảnh mới thì lưu file
        if (!empty($_FILES['Hinhanh']['name'])) {
            $uploadPath = __DIR__ . '/../../../uploads/movies/';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            if (!move_uploaded_file($_FILES['Hinhanh']['tmp_name'], $uploadPath . $imageName)) {
                $errors['Hinhanh'] = "Không thể lưu file ảnh poster mới.";
            }
        }
    }

    if (empty($errors)) {
        // Escape dữ liệu
        $TenPhim_sql       = mysqli_real_escape_string($conn, $old['TenPhim']);
        $ThoiLuong_sql     = (int)$old['ThoiLuong'];
        $TheLoai_sql       = mysqli_real_escape_string($conn, $old['TheLoai']);
        $DaoDien_sql       = mysqli_real_escape_string($conn, $old['DaoDien']);
        $DienVien_sql      = mysqli_real_escape_string($conn, $old['DienVien']);
        $NgayKhoiChieu_sql = mysqli_real_escape_string($conn, $old['NgayKhoiChieu']);
        $NgonNgu_sql       = mysqli_real_escape_string($conn, $old['NgonNgu']);
        $MoTa_sql          = mysqli_real_escape_string($conn, $old['MoTa']);
        $imageName_sql     = mysqli_real_escape_string($conn, $imageName);

        $sql_update = "
            UPDATE phim
            SET 
                TenPhim       = '$TenPhim_sql',
                ThoiLuong     = $ThoiLuong_sql,
                TheLoai       = '$TheLoai_sql',
                DaoDien       = '$DaoDien_sql',
                DienVien      = '$DienVien_sql',
                NgayKhoiChieu = '$NgayKhoiChieu_sql',
                NgonNgu       = '$NgonNgu_sql',
                MoTa          = '$MoTa_sql',
                Hinhanh       = '$imageName_sql'
            WHERE MaPhim = '$MaPhimParam_sql'
            LIMIT 1
        ";

        if (mysqli_query($conn, $sql_update)) {
            $success = "Cập nhật phim thành công.";
            // Nếu có poster mới → xóa poster cũ khỏi thư mục
            if ($isNewPoster && $currentPoster && file_exists($uploadPath . $currentPoster)) {
                @unlink($uploadPath . $currentPoster);
            }
            $currentPoster = $imageName;
        } else {
            $errors['global'] = "Lỗi khi cập nhật CSDL: " . mysqli_error($conn);
        }
    }
}

ob_start();
?>

<div class="card cgv-card shadow-sm p-4 mb-4">
    <div class="card-header cgv-card-header mb-4">
        <h4 class="mb-0"><i class="fas fa-edit me-2"></i> Sửa phim: <?= htmlspecialchars($old['MaPhim']) ?></h4>
    </div>

    <?php if (!empty($errors['global'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($errors['global']) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="cgv-form">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label text-light">Mã phim</label>
                <input type="text" name="MaPhim"
                    class="form-control cgv-input"
                    value="<?= htmlspecialchars($old['MaPhim']) ?>" readonly>
                <div class="form-text text-secondary">Mã phim không thể thay đổi.</div>
            </div>

            <div class="col-md-8 mb-3">
                <label class="form-label text-light">Tên phim</label>
                <input type="text" name="TenPhim"
                    class="form-control cgv-input <?= isset($errors['TenPhim']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['TenPhim']) ?>">
                <?php if (isset($errors['TenPhim'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['TenPhim']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label text-light">Thời lượng (phút)</label>
                <input type="number" name="ThoiLuong"
                    class="form-control cgv-input <?= isset($errors['ThoiLuong']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['ThoiLuong']) ?>">
                <?php if (isset($errors['ThoiLuong'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['ThoiLuong']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label text-light">Thể loại</label>
                <input type="text" name="TheLoai"
                    class="form-control cgv-input <?= isset($errors['TheLoai']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['TheLoai']) ?>">
                <?php if (isset($errors['TheLoai'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['TheLoai']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label text-light">Ngôn ngữ</label>
                <input type="text" name="NgonNgu"
                    class="form-control cgv-input <?= isset($errors['NgonNgu']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['NgonNgu']) ?>">
                <?php if (isset($errors['NgonNgu'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['NgonNgu']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-light">Đạo diễn</label>
                <input type="text" name="DaoDien"
                    class="form-control cgv-input <?= isset($errors['DaoDien']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['DaoDien']) ?>">
                <?php if (isset($errors['DaoDien'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['DaoDien']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label text-light">Diễn viên</label>
                <input type="text" name="DienVien"
                    class="form-control cgv-input <?= isset($errors['DienVien']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['DienVien']) ?>">
                <?php if (isset($errors['DienVien'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['DienVien']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-light">Ngày khởi chiếu</label>
                <input type="date" name="NgayKhoiChieu"
                    class="form-control cgv-input <?= isset($errors['NgayKhoiChieu']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['NgayKhoiChieu']) ?>">
                <?php if (isset($errors['NgayKhoiChieu'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['NgayKhoiChieu']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label text-light">Poster phim</label>
                <input type="file" name="Hinhanh"
                    class="form-control cgv-input <?= isset($errors['Hinhanh']) ? 'is-invalid' : '' ?>"
                    accept="image/*">
                <?php if (isset($errors['Hinhanh'])): ?>
                    <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['Hinhanh']) ?></div>
                <?php else: ?>
                    <div class="form-text text-secondary">
                        Để trống nếu muốn giữ nguyên poster cũ. Chỉ chấp nhận: jpg, jpeg, png, gif, webp (tối đa 2MB).
                    </div>
                <?php endif; ?>

                <?php if ($currentPoster): ?>
                    <div class="mt-2">
                        <div class="text-secondary small mb-1">Poster hiện tại:</div>
                        <img src="<?= '../../uploads/movies/' . htmlspecialchars($currentPoster) ?>"
                            alt="Poster hiện tại"
                            style="max-height: 160px; border-radius: 6px; border: 1px solid #333;">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label text-light">Mô tả phim</label>
            <textarea name="MoTa" rows="4"
                class="form-control cgv-input <?= isset($errors['MoTa']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($old['MoTa']) ?></textarea>
            <?php if (isset($errors['MoTa'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['MoTa']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mt-3 text-end">
            <a href="index.php?module=movie&action=list" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <button type="submit" class="btn cgv-btn-primary">
                <i class="fas fa-save"></i> Lưu thay đổi
            </button>
        </div>

    </form>
</div>

<script>
    // PREVIEW POSTER MỚI
    document.getElementById('inputHinhanh').addEventListener('change', function() {
        const file = this.files[0];
        const previewWrapper = document.getElementById('previewWrapper');
        const previewImage = document.getElementById('previewImage');

        if (!file) {
            previewWrapper.style.display = 'none';
            previewImage.src = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewWrapper.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/master.php';
