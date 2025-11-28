<?php
// admin/modules/showtime/index.php

$title = 'Quản lý suất chiếu - Chọn rạp';

ob_start();

// Lấy danh sách rạp
$sql = "SELECT MaRap, TenRap, DiaChi FROM rapchieu ORDER BY TenRap";
$result = mysqli_query($conn, $sql);
?>

<div class="card cgv-card shadow-sm p-4 mb-4">
    <div class="card-header cgv-card-header mb-4">
        <h4 class="mb-0">
            <i class="fas fa-film me-2"></i> Quản lý suất chiếu
        </h4>
    </div>

    <div class="row g-3">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="index.php?module=showtime&action=byRap&MaRap=<?= htmlspecialchars($row['MaRap']) ?>"
                        class="text-decoration-none">
                        <div class="cgv-card-rap p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0 text-light">
                                    <?= htmlspecialchars($row['TenRap']) ?>
                                </h5>
                                <span class="badge bg-danger">Mã: <?= htmlspecialchars($row['MaRap']) ?></span>
                            </div>
                            <div class="text-secondary small">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?= htmlspecialchars($row['DiaChi']) ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-muted">
                Chưa có rạp chiếu nào.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/master.php';
