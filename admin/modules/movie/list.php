<?php
// admin/modules/users/list.php

$title = 'Quản lí phim';

ob_start();

$limit = 3;

// Lấy filter/sort từ GET
$q        = trim($_GET['q']      ?? '');
$genre    = trim($_GET['genre']  ?? '');
$lang     = trim($_GET['lang']   ?? '');
$dateFrom = trim($_GET['from']   ?? '');
$dateTo   = trim($_GET['to']     ?? '');
$sort     = trim($_GET['sort']   ?? 'newest'); // newest|oldest|name_asc|name_desc|len_asc|len_desc

// Lấy trang hiện tại từ $_GET
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Xây điều kiện WHERE động
$conditions = [];
$conditions[] = '1=1';

// Tìm kiếm toàn cục
if ($q !== '') {
    $q_sql = mysqli_real_escape_string($conn, $q);
    $conditions[] = "(
        TenPhim   LIKE '%$q_sql%' OR
        TheLoai   LIKE '%$q_sql%' OR
        DaoDien   LIKE '%$q_sql%' OR
        DienVien  LIKE '%$q_sql%' OR
        NgonNgu   LIKE '%$q_sql%' OR
        MoTa      LIKE '%$q_sql%'
    )";
}

// Lọc thể loại
if ($genre !== '') {
    $genre_sql = mysqli_real_escape_string($conn, $genre);
    $conditions[] = "TheLoai LIKE '%$genre_sql%'";
}

// Lọc ngôn ngữ
if ($lang !== '') {
    $lang_sql = mysqli_real_escape_string($conn, $lang);
    $conditions[] = "NgonNgu LIKE '%$lang_sql%'";
}

// Lọc ngày khởi chiếu từ
if ($dateFrom !== '') {
    $tsFrom = strtotime($dateFrom);
    if ($tsFrom !== false) {
        $from_sql = date('Y-m-d', $tsFrom);
        $conditions[] = "NgayKhoiChieu >= '$from_sql'";
    }
}

// Lọc ngày khởi chiếu đến
if ($dateTo !== '') {
    $tsTo = strtotime($dateTo);
    if ($tsTo !== false) {
        $to_sql = date('Y-m-d', $tsTo);
        $conditions[] = "NgayKhoiChieu <= '$to_sql'";
    }
}

$whereSql = implode(' AND ', $conditions);

// Sắp xếp
switch ($sort) {
    case 'oldest':
        $orderBy = 'NgayKhoiChieu ASC, TenPhim ASC';
        break;
    case 'name_asc':
        $orderBy = 'TenPhim ASC';
        break;
    case 'name_desc':
        $orderBy = 'TenPhim DESC';
        break;
    case 'len_asc':
        $orderBy = 'ThoiLuong ASC, TenPhim ASC';
        break;
    case 'len_desc':
        $orderBy = 'ThoiLuong DESC, TenPhim ASC';
        break;
    case 'newest':
    default:
        $orderBy = 'NgayKhoiChieu DESC, TenPhim ASC';
        break;
}

// Đếm tổng số phim theo filter
$sql_count = "SELECT COUNT(*) AS total FROM phim WHERE $whereSql";
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

// Lấy danh sách phim theo filter + sort + phân trang
$sql = "
    SELECT 
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
    FROM phim
    WHERE $whereSql
    ORDER BY $orderBy
    LIMIT {$limit} OFFSET {$offset};
";
$result = mysqli_query($conn, $sql);

// Lấy danh sách thể loại & ngôn ngữ để fill dropdown
$sql_genre = "SELECT DISTINCT TheLoai FROM phim WHERE TheLoai IS NOT NULL AND TheLoai <> '' ORDER BY TheLoai";
$res_genre = mysqli_query($conn, $sql_genre);

$sql_lang = "SELECT DISTINCT NgonNgu FROM phim WHERE NgonNgu IS NOT NULL AND NgonNgu <> '' ORDER BY NgonNgu";
$res_lang = mysqli_query($conn, $sql_lang);

// Chuẩn bị query string cho pagination (giữ filter & sort)
$baseParams = [
    'module' => 'movie',
    'action' => 'list',
    'q'      => $q,
    'genre'  => $genre,
    'lang'   => $lang,
    'from'   => $dateFrom,
    'to'     => $dateTo,
    'sort'   => $sort,
];
$baseQuery = http_build_query($baseParams);
?>

<div class="card cgv-card shadow-sm">
    <div class="card-header cgv-card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-film me-2"></i> Quản lý phim
            </h5>
            <div class="small text-light mt-1">
                Tổng: <?= $total_records ?> phim
                <?php if ($q || $genre || $lang || $dateFrom || $dateTo): ?>
                    <span class="text-secondary">– Đang áp dụng bộ lọc</span>
                <?php endif; ?>
            </div>
        </div>
        <a href="index.php?module=movie&action=create" class="btn btn-sm cgv-btn-primary">
            <i class="fas fa-plus"></i> Thêm phim
        </a>
    </div>

    <!-- Thanh tìm kiếm / filter nâng cao -->
    <div class="card-body border-bottom cgv-filter-bar">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="module" value="movie">
            <input type="hidden" name="action" value="list">

            <div class="col-md-4">
                <label class="form-label text-light small mb-1">Tìm kiếm</label>
                <input type="text" name="q"
                    class="form-control cgv-input"
                    placeholder="Tên phim, đạo diễn, diễn viên, thể loại..."
                    value="<?= htmlspecialchars($q) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label text-light small mb-1">Thể loại</label>
                <select name="genre" class="form-select cgv-input">
                    <option value="">Tất cả</option>
                    <?php if ($res_genre): ?>
                        <?php while ($g = mysqli_fetch_assoc($res_genre)): ?>
                            <option value="<?= htmlspecialchars($g['TheLoai']) ?>"
                                <?= $genre === $g['TheLoai'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['TheLoai']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-light small mb-1">Ngôn ngữ</label>
                <select name="lang" class="form-select cgv-input">
                    <option value="">Tất cả</option>
                    <?php if ($res_lang): ?>
                        <?php while ($l = mysqli_fetch_assoc($res_lang)): ?>
                            <option value="<?= htmlspecialchars($l['NgonNgu']) ?>"
                                <?= $lang === $l['NgonNgu'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['NgonNgu']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-light small mb-1">Từ ngày</label>
                <input type="date" name="from"
                    class="form-control cgv-input"
                    value="<?= htmlspecialchars($dateFrom) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label text-light small mb-1">Đến ngày</label>
                <input type="date" name="to"
                    class="form-control cgv-input"
                    value="<?= htmlspecialchars($dateTo) ?>">
            </div>

            <div class="col-md-3 mt-2">
                <label class="form-label text-light small mb-1">Sắp xếp</label>
                <select name="sort" class="form-select cgv-input">
                    <option value="newest" <?= $sort === 'newest'   ? 'selected' : '' ?>>Khởi chiếu mới nhất</option>
                    <option value="oldest" <?= $sort === 'oldest'   ? 'selected' : '' ?>>Khởi chiếu cũ nhất</option>
                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Tên A → Z</option>
                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Tên Z → A</option>
                    <option value="len_asc" <?= $sort === 'len_asc'  ? 'selected' : '' ?>>Thời lượng tăng dần</option>
                    <option value="len_desc" <?= $sort === 'len_desc' ? 'selected' : '' ?>>Thời lượng giảm dần</option>
                </select>
            </div>

            <div class="col-md-3 mt-2 d-flex gap-2">
                <button type="submit" class="btn cgv-btn-primary flex-grow-1">
                    <i class="fas fa-search"></i> Lọc phim
                </button>
                <a href="index.php?module=movie&action=list" class="btn btn-outline-light btn-sm">
                    Xóa lọc
                </a>
            </div>
        </form>
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
                            // Xử lý ảnh: online / local
                            $poster = isset($rows['Hinhanh']) ? trim($rows['Hinhanh']) : '';
                            if ($poster !== '' && preg_match('~^https?://~', $poster)) {
                                $srcPoster = $poster;
                            } elseif ($poster !== '') {
                                // nếu bạn muốn dùng USER_URL thì sửa lại dòng dưới
                                $srcPoster = USER_URL . '/uploads/movies/' . ltrim($poster, '/');
                            } else {
                                // fallback nếu không có ảnh (nếu bạn có ảnh mặc định thì sửa path)
                                $srcPoster = USER_URL . '/assets/images/no-poster.png';
                            }

                            echo '          <img src="' . htmlspecialchars($srcPoster) . '" class="cgv-poster" alt="' . htmlspecialchars($rows['TenPhim']) . '">';
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
                        echo '  <td colspan="9" class="text-center text-muted py-4">Không tìm thấy phim nào với bộ lọc hiện tại.</td>';
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
                        echo '  <a class="page-link" href="index.php?' . $baseQuery . '&page=' . $prev_page . '" aria-label="Previous">';
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
                            echo '  <a class="page-link" href="index.php?' . $baseQuery . '&page=' . $i . '">' . $i . '</a>';
                            echo '</li>';
                        }
                    }

                    $next_page = $current_page + 1;
                    if ($current_page < $total_pages) {
                        echo '<li class="page-item">';
                        echo '  <a class="page-link" href="index.php?' . $baseQuery . '&page=' . $next_page . '" aria-label="Next">';
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