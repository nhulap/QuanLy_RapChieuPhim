<?php
// admin/modules/showtime/byRap.php

$title = 'Quản lý suất chiếu - Chọn phim';

if (!isset($_GET['MaRap']) || $_GET['MaRap'] === '') {
    die('Thiếu mã rạp.');
}

$MaRap = mysqli_real_escape_string($conn, $_GET['MaRap']);

// Lấy range filter: all | today | week | coming
$range = $_GET['range'] ?? 'all';
$allowedRanges = ['all', 'today', 'week', 'coming'];
if (!in_array($range, $allowedRanges, true)) {
    $range = 'all';
}

// Thông tin rạp
$sql_rap = "SELECT * FROM rapchieu WHERE MaRap = '$MaRap' LIMIT 1";
$res_rap = mysqli_query($conn, $sql_rap);
if (!$res_rap || mysqli_num_rows($res_rap) == 0) {
    die('Không tìm thấy rạp.');
}
$rap = mysqli_fetch_assoc($res_rap);

// Điều kiện thời gian cho CASE đếm suất chiếu
switch ($range) {
    case 'today':
        $dateCondition = "DATE(s.ThoiGianBatDau) = CURDATE()";
        $rangeLabel = 'hôm nay';
        break;
    case 'week':
        $dateCondition = "DATE(s.ThoiGianBatDau) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        $rangeLabel = '7 ngày tới';
        break;
    case 'coming':
        $dateCondition = "DATE(s.ThoiGianBatDau) >= CURDATE()";
        $rangeLabel = 'từ hôm nay trở đi';
        break;
    default:
        $dateCondition = "1=1"; // không lọc
        $rangeLabel = 'tất cả thời gian';
        break;
}

/*
 * Lấy TẤT CẢ PHIM trong hệ thống,
 * và đếm số suất chiếu của từng phim tại rạp này
 * trong khoảng thời gian đã chọn (range).
 */
$sql = "
    SELECT 
        p.MaPhim,
        p.TenPhim,
        p.Hinhanh,
        p.NgayKhoiChieu,
        p.TheLoai,
        SUM(
            CASE 
                WHEN pc.MaRap = '$MaRap' AND $dateCondition THEN 1
                ELSE 0
            END
        ) AS SoSuat
    FROM phim p
    LEFT JOIN suatchieu s ON s.MaPhim = p.MaPhim
    LEFT JOIN phongchieu pc ON s.MaPhong = pc.MaPhong
    GROUP BY 
        p.MaPhim,
        p.TenPhim,
        p.Hinhanh,
        p.NgayKhoiChieu,
        p.TheLoai
    ORDER BY p.NgayKhoiChieu, p.TenPhim
";

$result = mysqli_query($conn, $sql);

ob_start();
?>

<div class="card cgv-card shadow-sm p-4 mb-4">
    <div class="card-header cgv-card-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-theater-masks me-2"></i>
                    Suất chiếu – <?= htmlspecialchars($rap['TenRap']) ?>
                </h4>
                <div class="text-light small">
                    <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($rap['DiaChi']) ?>
                </div>
                <div class="text-secondary small mt-1">
                    Đang xem suất chiếu trong khoảng: <strong><?= htmlspecialchars($rangeLabel) ?></strong>
                </div>
            </div>

        </div>
    </div>

    <!-- Thanh filter thời gian -->
    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="Filter thời gian suất chiếu">
            <a href="index.php?module=showtime&action=byRap&MaRap=<?= htmlspecialchars($MaRap) ?>&range=all"
                class="btn <?= $range === 'all' ? 'btn-danger' : 'btn-outline-light' ?>">
                Tất cả
            </a>
            <a href="index.php?module=showtime&action=byRap&MaRap=<?= htmlspecialchars($MaRap) ?>&range=today"
                class="btn <?= $range === 'today' ? 'btn-danger' : 'btn-outline-light' ?>">
                Hôm nay
            </a>
            <a href="index.php?module=showtime&action=byRap&MaRap=<?= htmlspecialchars($MaRap) ?>&range=week"
                class="btn <?= $range === 'week' ? 'btn-danger' : 'btn-outline-light' ?>">
                7 ngày tới
            </a>
            <a href="index.php?module=showtime&action=byRap&MaRap=<?= htmlspecialchars($MaRap) ?>&range=coming"
                class="btn <?= $range === 'coming' ? 'btn-danger' : 'btn-outline-light' ?>">
                Từ hôm nay
            </a>
        </div>
    </div>

    <div class="row g-3">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                // Xác định trạng thái phim theo ngày khởi chiếu
                $today = date('Y-m-d');
                $ngayKhoiChieu = $row['NgayKhoiChieu'];
                $statusText = '';
                $statusClass = '';

                if ($ngayKhoiChieu > $today) {
                    $statusText = 'Sắp chiếu';
                    $statusClass = 'badge bg-warning text-dark';
                } else {
                    $statusText = 'Đang chiếu / đã chiếu';
                    $statusClass = 'badge bg-success';
                }

                $soSuat = (int)$row['SoSuat'];
                ?>
                <div class="col-md-6 col-lg-4">
                    <a href="index.php?module=showtime&action=list&MaRap=<?= htmlspecialchars($MaRap) ?>&MaPhim=<?= htmlspecialchars($row['MaPhim']) ?>"
                        class="text-decoration-none">
                        <div class="cgv-card-movie p-2 h-100 d-flex">
                            <div class="cgv-poster-wrap me-2" style="width:90px;">
                                <?php
                                $poster = isset($row['Hinhanh']) ? trim($row['Hinhanh']) : '';
                                if ($poster !== '' && preg_match('~^https?://~', $poster)) {
                                    $srcPoster = $poster;
                                } elseif ($poster !== '') {
                                    $srcPoster = '../uploads/movies/' . ltrim($poster, '/');
                                } else {
                                    $srcPoster = '../assets/images/no-poster.png'; // nếu có ảnh mặc định
                                }
                                ?>
                                <img src="<?= htmlspecialchars($srcPoster) ?>"
                                    class="cgv-poster"
                                    alt="<?= htmlspecialchars($row['TenPhim']) ?>">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-light mb-1" style="line-height:1.2;">
                                    <?= htmlspecialchars($row['TenPhim']) ?>
                                </h6>
                                <div class="text-secondary small mb-1">
                                    <?= htmlspecialchars($row['TheLoai']) ?>
                                </div>
                                <div class="text-secondary small mb-2">
                                    Khởi chiếu: <?= date('d/m/Y', strtotime($row['NgayKhoiChieu'])) ?>
                                </div>

                                <div class="mb-1">
                                    <span class="<?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>
                                </div>

                                <?php if ($soSuat > 0): ?>
                                    <div class="small text-danger fw-semibold">
                                        Đang có <?= $soSuat ?> suất chiếu trong <?= htmlspecialchars($rangeLabel) ?> tại rạp này
                                    </div>
                                <?php else: ?>
                                    <div class="small text-secondary">
                                        Chưa có suất chiếu nào trong <?= htmlspecialchars($rangeLabel) ?> tại rạp này
                                    </div>
                                <?php endif; ?>

                                <div class="mt-2">
                                    <span class="badge bg-outline-light border text-light">
                                        Quản lý suất chiếu
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-muted">
                Chưa có phim nào trong hệ thống.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/master.php';
