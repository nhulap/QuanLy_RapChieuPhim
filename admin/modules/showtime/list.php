<?php
// admin/modules/showtime/list.php

$title = 'Quản lý suất chiếu';

if (!isset($_GET['MaRap']) || !isset($_GET['MaPhim'])) {
    die('Thiếu tham số MaRap hoặc MaPhim.');
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

// Lấy suất chiếu của phim này tại rạp này
$sql = "
    SELECT 
        s.MaSuatChieu,
        s.ThoiGianBatDau,
        s.GiaVeCoBan,
        pc.MaPhong,
        pc.TenPhong,
        pc.LoaiPhong
    FROM suatchieu s
    JOIN phongchieu pc ON s.MaPhong = pc.MaPhong
    WHERE s.MaPhim = '$MaPhim' AND pc.MaRap = '$MaRap'
    ORDER BY s.ThoiGianBatDau
";
$result = mysqli_query($conn, $sql);

ob_start();
?>

<div class="card cgv-card shadow-sm">
    <div class="card-header cgv-card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">
                <i class="fas fa-clock me-2"></i>
                Suất chiếu – <?= htmlspecialchars($phim['TenPhim']) ?>
            </h5>
            <div class="small text-light">
                Rạp: <?= htmlspecialchars($rap['TenRap']) ?> (<?= htmlspecialchars($MaRap) ?>)
            </div>
        </div>
        <div>
            <a href="index.php?module=showtime&action=byRap&MaRap=<?= htmlspecialchars($MaRap) ?>" class="btn btn-sm btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Chọn phim khác
            </a>
            <a href="index.php?module=showtime&action=create&MaRap=<?= htmlspecialchars($MaRap) ?>&MaPhim=<?= htmlspecialchars($MaPhim) ?>"
                class="btn btn-sm cgv-btn-primary">
                <i class="fas fa-plus"></i> Thêm suất chiếu
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 cgv-table">
                <thead>
                    <tr>
                        <th>Mã suất</th>
                        <th>Phòng chiếu</th>
                        <th>Loại phòng</th>
                        <th>Thời gian bắt đầu</th>
                        <th>Giá vé cơ bản</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['MaSuatChieu']) ?></td>
                                <td><?= htmlspecialchars($row['TenPhong']) ?> (<?= htmlspecialchars($row['MaPhong']) ?>)</td>
                                <td><?= htmlspecialchars($row['LoaiPhong']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['ThoiGianBatDau'])) ?></td>
                                <td><?= number_format($row['GiaVeCoBan'], 0, ',', '.') ?> đ</td>
                                <td class="text-end">
                                    <a href="index.php?module=showtime&action=edit&MaRap=<?= htmlspecialchars($MaRap) ?>&MaPhim=<?= htmlspecialchars($MaPhim) ?>&MaSuatChieu=<?= htmlspecialchars($row['MaSuatChieu']) ?>"
                                        class="btn btn-sm cgv-btn-icon" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button type="button"
                                        class="btn btn-sm cgv-btn-icon cgv-btn-danger"
                                        title="Xóa suất chiếu"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteShowtimeModal"
                                        data-masuatchieu="<?= htmlspecialchars($row['MaSuatChieu']) ?>"
                                        data-marap="<?= htmlspecialchars($MaRap) ?>"
                                        data-maphim="<?= htmlspecialchars($MaPhim) ?>"
                                        data-time="<?= date('d/m/Y H:i', strtotime($row['ThoiGianBatDau'])) ?>"
                                        data-room="<?= htmlspecialchars($row['TenPhong']) ?> (<?= htmlspecialchars($row['MaPhong']) ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>

                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Chưa có suất chiếu nào cho phim này tại rạp này.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal XÓA SUẤT CHIẾU – CGV STYLE -->
<div class="modal fade cgv-modal" id="deleteShowtimeModal" tabindex="-1" aria-labelledby="deleteShowtimeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgv-modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-light" id="deleteShowtimeModalLabel">
                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i> Xác nhận xóa suất chiếu
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <p class="mb-2 text-light">
                    Bạn có chắc chắn muốn xóa suất chiếu sau:
                </p>
                <ul class="list-unstyled small text-secondary mb-3">
                    <li><span class="text-light">Mã suất:</span> <span id="scMa"></span></li>
                    <li><span class="text-light">Phòng:</span> <span id="scPhong"></span></li>
                    <li><span class="text-light">Thời gian:</span> <span id="scTime"></span></li>
                </ul>
                <p class="text-secondary small mb-0">
                    Thao tác này không thể hoàn tác. Vé đã đặt (nếu có) cho suất chiếu này sẽ không còn hiệu lực.
                </p>
            </div>

            <div class="modal-footer border-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">
                    Hủy
                </button>

                <form method="post" action="index.php?module=showtime&action=delete" class="m-0">
                    <input type="hidden" name="MaRap" id="deleteMaRap">
                    <input type="hidden" name="MaPhim" id="deleteMaPhim">
                    <input type="hidden" name="MaSuatChieu" id="deleteMaSuatChieu">
                    <button type="submit" class="btn cgv-btn-danger px-3">
                        <i class="fas fa-trash-alt me-1"></i> Xóa suất chiếu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Đổ dữ liệu vào modal khi mở
    var deleteShowtimeModal = document.getElementById('deleteShowtimeModal');
    if (deleteShowtimeModal) {
        deleteShowtimeModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            if (!button) return;

            var maSuat = button.getAttribute('data-masuatchieu');
            var maRap = button.getAttribute('data-marap');
            var maPhim = button.getAttribute('data-maphim');
            var time = button.getAttribute('data-time');
            var room = button.getAttribute('data-room');

            document.getElementById('deleteMaSuatChieu').value = maSuat;
            document.getElementById('deleteMaRap').value = maRap;
            document.getElementById('deleteMaPhim').value = maPhim;

            document.getElementById('scMa').textContent = maSuat;
            document.getElementById('scPhong').textContent = room;
            document.getElementById('scTime').textContent = time;
        });
    }
</script>


<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/master.php';
