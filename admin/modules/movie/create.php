<?php
$title = "Thêm phim mới";

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // LẤY DỮ LIỆU
    $MaPhim       = trim($_POST['MaPhim']);
    $TenPhim      = trim($_POST['TenPhim']);
    $ThoiLuong    = trim($_POST['ThoiLuong']);
    $TheLoai      = trim($_POST['TheLoai']);
    $DaoDien      = trim($_POST['DaoDien']);
    $DienVien     = trim($_POST['DienVien']);
    $NgayKhoiChieu = trim($_POST['NgayKhoiChieu']);
    $NgonNgu      = trim($_POST['NgonNgu']);
    $MoTa         = trim($_POST['MoTa']);

    // VALIDATION
    if ($MaPhim == "")      $errors[] = "Mã phim không được để trống.";
    if ($TenPhim == "")     $errors[] = "Tên phim không được để trống.";
    if ($ThoiLuong == "" || !is_numeric($ThoiLuong)) $errors[] = "Thời lượng phải là số.";
    if ($TheLoai == "")     $errors[] = "Thể loại không được để trống.";
    if ($DaoDien == "")     $errors[] = "Đạo diễn không được để trống.";
    if ($DienVien == "")    $errors[] = "Diễn viên không được để trống.";
    if ($NgayKhoiChieu == "") $errors[] = "Ngày khởi chiếu không được để trống.";
    if ($NgonNgu == "")     $errors[] = "Ngôn ngữ không được để trống.";

    $check_existing_movie = "SELECT * from phim where MaPhim = '$MaPhim'";
    $result = mysqli_query($conn, $check_existing_movie);

    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Mã phim đã tồn tại!";
    } else {
        // VALIDATE ẢNH
        if (!empty($_FILES['Hinhanh']['name'])) {

            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['Hinhanh']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $errors[] = "Ảnh không hợp lệ! Chỉ nhận jpg, jpeg, png, gif, webp.";
            }

            // Tên ảnh
            $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;
        } else {
            $errors[] = "Vui lòng chọn ảnh poster.";
        }

        // Nếu không lỗi → lưu DB
        if (empty($errors)) {

            // Tạo thư mục upload nếu chưa có
            $uploadPath = __DIR__ . '/../../../uploads/movies/';
            if (!file_exists($uploadPath)) mkdir($uploadPath, 0777, true);

            move_uploaded_file($_FILES['Hinhanh']['tmp_name'], $uploadPath . $imageName);

            $sql = "INSERT INTO phim 
        (MaPhim, TenPhim, ThoiLuong, TheLoai, DaoDien, DienVien, NgayKhoiChieu, NgonNgu, MoTa, Hinhanh)
        VALUES 
        ('$MaPhim', '$TenPhim', '$ThoiLuong', '$TheLoai', '$DaoDien', '$DienVien', '$NgayKhoiChieu', '$NgonNgu', '$MoTa', '$imageName')";

            if (mysqli_query($conn, $sql)) {
                $success = "Thêm phim thành công!";
            } else {
                $errors[] = "Lỗi khi thêm vào CSDL: " . mysqli_error($conn);
            }
        }
    }
}

ob_start();
?>

<div class="card cgv-card shadow-sm p-4">
    <div class="card-header cgv-card-header mb-4">
        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Thêm phim mới</h4>
    </div>

    <!-- THÔNG BÁO -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo "<div>• $e</div>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="cgv-form">

        <div class="row">
            <div class="col-md-4">
                <label class="form-label text-light">Mã phim</label>
                <input type="text" name="MaPhim" class="form-control cgv-input" required>
            </div>

            <div class="col-md-8">
                <label class="form-label text-light">Tên phim</label>
                <input type="text" name="TenPhim" class="form-control cgv-input" required value="<?php if (isset($TenPhim)) echo $TenPhim ?>">
            </div>
        </div>

        <div class="row mt-3">

            <div class="col-md-4">
                <label class="form-label text-light">Thời lượng (phút)</label>
                <input type="number" name="ThoiLuong" class="form-control cgv-input" required value="<?php if (isset($ThoiLuong)) echo $ThoiLuong ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label text-light">Thể loại</label>
                <input type="text" name="TheLoai" class="form-control cgv-input" required value="<?php if (isset($TheLoai)) echo $TheLoai ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label text-light">Ngôn ngữ</label>
                <input type="text" name="NgonNgu" class="form-control cgv-input" required value="<?php if (isset($NgonNgu)) echo $NgonNgu ?>">
            </div>
        </div>

        <div class="row mt-3">

            <div class="col-md-6">
                <label class="form-label text-light">Đạo diễn</label>
                <input type="text" name="DaoDien" class="form-control cgv-input" required value="<?php if (isset($DaoDien)) echo $DaoDien ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label text-light">Diễn viên</label>
                <input type="text" name="DienVien" class="form-control cgv-input" required value="<?php if (isset($DienVien)) echo $DienVien ?>">
            </div>
        </div>

        <div class="row mt-3">

            <div class="col-md-6">
                <label class="form-label text-light">Ngày khởi chiếu</label>
                <input type="date" name="NgayKhoiChieu" class="form-control cgv-input" required value="<?php if (isset($NgayKhoiChieu)) echo $NgayKhoiChieu ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label text-light">Poster phim</label>
                <input type="file" name="Hinhanh" class="form-control cgv-input" accept="image/*" required>
            </div>

        </div>

        <div class="mt-3">
            <label class="form-label text-light">Mô tả phim</label>
            <textarea name="MoTa" rows="4" class="form-control cgv-input"><?php if (isset($MoTa)) echo $MoTa ?></textarea>
        </div>

        <div class="mt-4 text-end">
            <a href="index.php?module=movie&action=list" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Hủy
            </a>

            <button type="submit" class="btn cgv-btn-primary">
                <i class="fas fa-save"></i> Lưu phim
            </button>
        </div>

    </form>
</div>

<?php
$content = ob_get_clean(); // lấy nội dung buffer đưa vào $content

include __DIR__ . '/../../layouts/master.php';
?>