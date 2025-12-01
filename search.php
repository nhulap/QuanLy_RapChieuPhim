<?php
    session_start();
    require "Connection.php";
    require_once __DIR__ . '/config/config.php'; 
    $page_title = "Trang chủ - CGV";
    include 'layout/header.php'; 

    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : "";
    $theloai  = isset($_GET['theloai']) ? trim($_GET['theloai']) : "";
    $amthanh  = isset($_GET['amthanh']) ? trim($_GET['amthanh']) : "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./stylelap.css">

    <title><?php echo $page_title ?? "CGV - Rạp Chiếu Phim"; ?></title>
</head>


<div>

    <h2 style="padding: 10px;">Kết quả tìm kiếm cho: <?php echo htmlspecialchars($keyword); ?></h2>
    <div class="filter-container">

        <button class="filter-btn" onclick="toggleFilter()">Bộ lọc</button>

        <div class="filter-box" id="filterBox">
            <form action="" method="GET">

                <select name="theloai">
                    <option value="">--Thể loại--</option>
                    <option value="Tình cảm">Tình cảm</option>
                    <option value="Hoạt hình">Hoạt hình</option>
                    <option value="Hành động">Hành động</option>
                    <option value="Kịch tính">Kịch tính</option>
                    <option value="Khoa Học Viễn Tưởng">Khoa Học Viễn Tưởng</option>
                    <option value="Kịch tính">Hài</option>
                    <option value="Kinh dị">Kinh dị</option>
                </select>

                <select name="amthanh">
                    <option value="">--Âm thanh--</option>
                    <option value="Lồng tiếng">Lồng tiếng</option>
                    <option value="Phụ đề">Phụ đề</option>
                </select>

                <button type="submit">Lọc</button>
            </form>
        </div>
    </div>
    <hr>

    <?php
    $sql = "SELECT * FROM phim WHERE 1 ";
    $conditions = [];

    if ($keyword !== "") {
        $keyword_db = $conn->real_escape_string($keyword);
        $conditions[] = "(TenPhim LIKE '%$keyword_db%' OR TheLoai LIKE '%$keyword_db%')";
    }

    if ($theloai !== "") {
        $theloai_db = $conn->real_escape_string($theloai);
        $conditions[] = "TheLoai LIKE '%$theloai_db%'";
    }

    if ($amthanh !== "") {
        $amthanh_db = $conn->real_escape_string($amthanh);
        $conditions[] = "NgonNgu LIKE '%$amthanh_db%'";
    }

    // Ghép điều kiện
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        echo "<div class='kq_tim'>";

        while ($row = $result->fetch_assoc()) {
            echo '<a href="chi_tiet_phim/chi_tiet_phim.php?MaPhim=' . urlencode($row["MaPhim"]) . '" class="movie-link">';
            echo "
            <div class='movie-card_search'>
                <img src='{$row['Hinhanh']}'>
                <h3>{$row['TenPhim']}</h3>
                <p>Thể loại: {$row['TheLoai']}</p>
            </div>";
            echo "</a>";
        }

        echo "</div>";
    } else {
        echo "<p style='padding: 15px;'>Không tìm thấy phim phù hợp.</p>";
    }

    $conn->close();
    ?>

</div>
<script>
    function toggleFilter() {
        const box = document.getElementById("filterBox");
        box.style.display = box.style.display === "block" ? "none" : "block";
    }
</script>

