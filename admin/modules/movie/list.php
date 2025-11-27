<?php
// admin/modules/users/list.php

$title = 'Quản lí phim';

ob_start();

$limit = 3;

// Lấy trang hiện tại từ $_GET
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Đếm tổng số phim
$sql_count = "SELECT COUNT(*) AS total FROM phim";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = (int)$row_count['total'];

// Tính tổng số trang
$total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;

// Giới hạn current_page không vượt quá total_pages
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

// Tính offset
$offset = ($current_page - 1) * $limit;


$sql = "SELECT 
    MaPhim,
    TenPhim,
    ThoiLuong,
    TheLoai,
    DaoDien,
    DienVien,
    NgayKhoiChieu,
    NgonNgu,
    MoTa,
    Hinhanh
    FROM phim  ORDER BY NgayKhoiChieu DESC
    LIMIT {$limit} OFFSET {$offset}; 
    ";

$result = mysqli_query($conn, $sql);

?>

<div class="card cgv-card shadow-sm">
    <div class="card-header cgv-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-film me-2"></i> Quản lý phim
        </h5>
        <a href="index.php?module=movie&action=create" class="btn btn-sm cgv-btn-primary">
            <i class="fas fa-plus"></i> Thêm phim
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 cgv-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Tên phim</th>
                        <th>Thể loại</th>
                        <th>Thời lượng</th>
                        <th>Đạo diễn</th>
                        <th>Diễn viên</th>
                        <th>Khởi chiếu</th>
                        <th>Ngôn ngữ</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($result) > 0) {
                        while ($rows = mysqli_fetch_array($result)) {
                            echo '<tr>';

                            echo '  <td>';
                            echo '      <div class="cgv-poster-wrap">';
                            if (str_contains($rows['Hinhanh'], 'https://www')) {
                                echo '<img src="' . htmlspecialchars($rows['Hinhanh']) . '" class="cgv-poster" alt="' . htmlspecialchars($rows['TenPhim']) . '">';
                            } else {
                                echo '<img src="' . USER_URL . '/uploads/movies/' . htmlspecialchars($rows['Hinhanh']) . '" class="cgv-poster" alt="' . htmlspecialchars($rows['TenPhim']) . '">';
                            }

                            echo '      </div>';
                            echo '  </td>';

                            echo '  <td>';
                            echo '      <strong>' . htmlspecialchars($rows['TenPhim']) . '</strong>';
                            echo '      <div class="small text-muted" style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">';
                            echo            htmlspecialchars($rows['MoTa']);
                            echo '      </div>';
                            echo '  </td>';

                            echo '  <td>' . htmlspecialchars($rows['TheLoai']) . '</td>';

                            echo '  <td>' . (int)$rows['ThoiLuong'] . ' phút</td>';
                            echo '  <td>' . htmlspecialchars($rows['DaoDien']) . '</td>';
                            echo '  <td style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'
                                . htmlspecialchars($rows['DienVien']) .
                                '</td>';
                            echo '  <td>' . date("d/m/Y", strtotime($rows['NgayKhoiChieu'])) . '</td>';
                            echo '  <td>' . htmlspecialchars($rows['NgonNgu']) . '</td>';
                            echo '  <td class="text-end">';

                            echo '      <a href="index.php?module=movie&action=edit&id=' . $rows['MaPhim'] . '" ';
                            echo '         class="btn btn-sm cgv-btn-icon" title="Sửa">';
                            echo '          <i class="fas fa-edit"></i>';
                            echo '      </a>';

                            echo '      <button type="button" ';
                            echo '         class="btn btn-sm cgv-btn-icon cgv-btn-danger" ';
                            echo '         title="Xóa" ';
                            echo '         data-bs-toggle="modal" ';
                            echo '         data-bs-target="#deleteMovieModal" ';
                            echo '         data-id="' . htmlspecialchars($rows['MaPhim']) . '" ';
                            echo '         data-title="' . htmlspecialchars($rows['TenPhim']) . '">';
                            echo '          <i class="fas fa-trash-alt"></i>';
                            echo '      </button>';

                            echo '  </td>';

                            echo '</tr>';
                        }
                    } else {
                        echo '<tr>';
                        echo '  <td colspan="9" class="text-center text-muted py-4">Chưa có phim nào.</td>';
                        echo '</tr>';
                    }
                    ?>


                </tbody>

            </table>
        </div>

    </div>
    <?php if ($total_pages > 1): ?>
        <div class="cgv-pagination-wrapper pt-4">
            <nav aria-label="CGV movie pagination">
                <ul class="pagination justify-content-center cgv-pagination mb-0">

                    <?php
                    $prev_page = $current_page - 1;
                    if ($current_page > 1) {
                        echo '<li class="page-item">';
                        echo '  <a class="page-link" href="index.php?module=movie&action=list&page=' . $prev_page . '" aria-label="Previous">';
                        echo '      <span aria-hidden="true">&laquo;</span>';
                        echo '  </a>';
                        echo '</li>';
                    } else {
                        echo '<li class="page-item disabled">';
                        echo '  <span class="page-link" aria-label="Previous"><span aria-hidden="true">&laquo;</span></span>';
                        echo '</li>';
                    }

                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i == $current_page) {
                            echo '<li class="page-item active">';
                            echo '  <span class="page-link">' . $i . '</span>';
                            echo '</li>';
                        } else {
                            echo '<li class="page-item">';
                            echo '  <a class="page-link" href="index.php?module=movie&action=list&page=' . $i . '">' . $i . '</a>';
                            echo '</li>';
                        }
                    }

                    $next_page = $current_page + 1;
                    if ($current_page < $total_pages) {
                        echo '<li class="page-item">';
                        echo '  <a class="page-link" href="index.php?module=movie&action=list&page=' . $next_page . '" aria-label="Next">';
                        echo '      <span aria-hidden="true">&raquo;</span>';
                        echo '  </a>';
                        echo '</li>';
                    } else {
                        echo '<li class="page-item disabled">';
                        echo '  <span class="page-link" aria-label="Next"><span aria-hidden="true">&raquo;</span></span>';
                        echo '</li>';
                    }
                    ?>

                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Xóa phim - CGV Style -->
<div class="modal fade cgv-modal" id="deleteMovieModal" tabindex="-1" aria-labelledby="deleteMovieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgv-modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-light" id="deleteMovieModalLabel">
                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i> Xác nhận xóa phim
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-2 text-light">
                    Bạn có chắc chắn muốn xóa phim:
                </p>
                <p class="fw-bold text-warning mb-3" id="deleteMovieName">Tên phim</p>
                <p class="text-secondary small mb-0">
                    Thao tác này không thể hoàn tác. Poster và thông tin phim sẽ bị xóa khỏi hệ thống.
                </p>
            </div>

            <div class="modal-footer border-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">
                    Hủy
                </button>

                <form method="post" action="index.php?module=movie&action=delete" class="m-0">
                    <input type="hidden" name="MaPhim" id="deleteMovieId">
                    <button type="submit" class="btn cgv-btn-danger px-3">
                        <i class="fas fa-trash-alt me-1"></i> Xóa phim
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Truyền dữ liệu phim vào modal khi nhấn nút Xóa
    var deleteMovieModal = document.getElementById('deleteMovieModal');
    if (deleteMovieModal) {
        deleteMovieModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var movieId = button.getAttribute('data-id');
            var movieTitle = button.getAttribute('data-title');

            var movieIdInput = document.getElementById('deleteMovieId');
            var movieNameLabel = document.getElementById('deleteMovieName');

            if (movieIdInput) movieIdInput.value = movieId;
            if (movieNameLabel) movieNameLabel.textContent = movieTitle;
        });
    }
</script>


<?php
$content = ob_get_clean(); // lấy nội dung buffer đưa vào $content

include __DIR__ . '/../../layouts/master.php';
?>