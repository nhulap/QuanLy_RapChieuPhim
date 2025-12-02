<?php
// admin/modules/dashboard/index.php

$title = 'Dashboard';

// Hôm nay (phía PHP, chỉ dùng để hiển thị)
$today = date('Y-m-d');

// Chọn khoảng thời gian cho biểu đồ/top phim (mặc định 7 ngày)
$range = isset($_GET['range']) && $_GET['range'] == 30 ? 30 : 7;
$rangeLabel = $range == 7 ? '7 ngày gần nhất' : '30 ngày gần nhất';

// =========================
// 1. KPI: Doanh thu hôm nay
// =========================
$sqlRevenueToday = "
    select 
        coalesce(sum(TongTien), 0) as revenue_today,
        coalesce(sum(SoLuong), 0) as tickets_today
    from datve
    where date(ThoiGianDat) = curdate()
      and TrangThaiThanhToan = 'Thanh Toán Thành Công'
";
$resultRevenueToday = mysqli_query($conn, $sqlRevenueToday);
$rowRevenueToday = $resultRevenueToday ? mysqli_fetch_assoc($resultRevenueToday) : ['revenue_today' => 0, 'tickets_today' => 0];

$revenueToday = (float)$rowRevenueToday['revenue_today'];
$ticketsToday = (int)$rowRevenueToday['tickets_today'];

// ===============================================
// 2. Tỷ lệ lấp đầy ghế hôm nay (tính theo suất chiếu)
// ===============================================

// 2.1. Tổng số ghế theo phòng
$sqlSeatsByRoom = "select MaPhong, count(*) as TongGhe from ghe group by MaPhong";
$resultSeatsByRoom = mysqli_query($conn, $sqlSeatsByRoom);
$seatsPerRoom = [];
if ($resultSeatsByRoom) {
    while ($rowSeat = mysqli_fetch_assoc($resultSeatsByRoom)) {
        $seatsPerRoom[$rowSeat['MaPhong']] = (int)$rowSeat['TongGhe'];
    }
}

// 2.2. Lấy danh sách suất chiếu hôm nay + số vé đã đặt cho từng suất
$sqlShowsTodayStats = "
    select 
        sc.MaSuatChieu,
        sc.MaPhong,
        coalesce(sum(dv.SoLuong), 0) as DaDat
    from suatchieu sc
    left join datve dv
        on sc.MaSuatChieu = dv.MaSuatChieu
        and dv.TrangThaiThanhToan = 'Thanh Toán Thành Công'
    where date(sc.ThoiGianBatDau) = curdate()
    group by sc.MaSuatChieu, sc.MaPhong
";
$resultShowsTodayStats = mysqli_query($conn, $sqlShowsTodayStats);

$totalCapacityToday = 0;
$totalBookedToday = 0;

if ($resultShowsTodayStats) {
    while ($rowShowToday = mysqli_fetch_assoc($resultShowsTodayStats)) {
        $maPhongToday = $rowShowToday['MaPhong'];
        if (!isset($seatsPerRoom[$maPhongToday])) {
            continue;
        }

        $capacityToday = $seatsPerRoom[$maPhongToday];
        $bookedToday = (int)$rowShowToday['DaDat'];

        $totalCapacityToday += $capacityToday;
        $totalBookedToday += $bookedToday;
    }
}

$seatFillRateToday = $totalCapacityToday > 0
    ? round($totalBookedToday * 100 / $totalCapacityToday, 2)
    : 0;

// ==========================================
// 3. Số phim đang chiếu hôm nay (có suất chiếu)
// ==========================================
$sqlMoviesNowShowing = "
    select count(distinct MaPhim) as phim_dang_chieu
    from suatchieu
    where date(ThoiGianBatDau) = curdate()
";
$resultMoviesNowShowing = mysqli_query($conn, $sqlMoviesNowShowing);
$rowMoviesNowShowing = $resultMoviesNowShowing
    ? mysqli_fetch_assoc($resultMoviesNowShowing)
    : ['phim_dang_chieu' => 0];

$moviesNowShowing = (int)$rowMoviesNowShowing['phim_dang_chieu'];

// ========================================================
// 4. Biểu đồ xu hướng: doanh thu & số vé theo ngày (7/30d)
// ========================================================
$sqlTrend = "
    select 
        date(ThoiGianDat) as ngay,
        sum(TongTien) as doanhthu,
        sum(SoLuong) as sove
    from datve
    where ThoiGianDat >= date_sub(curdate(), interval {$range} day)
      and ThoiGianDat < date_add(curdate(), interval 1 day)
      and TrangThaiThanhToan = 'Thanh Toán Thành Công'
    group by date(ThoiGianDat)
    order by ngay asc
";
$resultTrend = mysqli_query($conn, $sqlTrend);

$chartLabels = [];
$chartRevenue = [];
$chartTickets = [];

if ($resultTrend) {
    while ($rowTrend = mysqli_fetch_assoc($resultTrend)) {
        $chartLabels[] = date('d/m', strtotime($rowTrend['ngay']));
        $chartRevenue[] = (float)$rowTrend['doanhthu'];
        $chartTickets[] = (int)$rowTrend['sove'];
    }
}

// ========================================================
// 5. Top phim theo doanh thu / số vé trong khoảng (7/30d)
// ========================================================
$sqlTopMovies = "
    select 
        p.TenPhim,
        sum(dv.TongTien) as doanhthu,
        sum(dv.SoLuong) as sove
    from datve dv
    join suatchieu sc on dv.MaSuatChieu = sc.MaSuatChieu
    join phim p on sc.MaPhim = p.MaPhim
    where dv.ThoiGianDat >= date_sub(curdate(), interval {$range} day)
      and dv.ThoiGianDat < date_add(curdate(), interval 1 day)
      and dv.TrangThaiThanhToan = 'Thanh Toán Thành Công'
    group by p.MaPhim
    order by doanhthu desc
    limit 5
";
$resultTopMovies = mysqli_query($conn, $sqlTopMovies);
$topMovies = [];
if ($resultTopMovies) {
    while ($rowTopMovie = mysqli_fetch_assoc($resultTopMovies)) {
        $topMovies[] = $rowTopMovie;
    }
}

// ======================================================
// 6. Phân bố doanh thu theo rạp/phòng trong khoảng (7/30d)
// ======================================================

// 6.1 Theo phòng
$sqlRevenueByRoom = "
    select 
        sc.MaPhong,
        coalesce(pc.TenPhong, sc.MaPhong) as TenPhong,
        sum(dv.TongTien) as doanhthu,
        sum(dv.SoLuong) as sove
    from datve dv
    join suatchieu sc on dv.MaSuatChieu = sc.MaSuatChieu
    left join phongchieu pc on sc.MaPhong = pc.MaPhong
    where dv.ThoiGianDat >= date_sub(curdate(), interval {$range} day)
      and dv.ThoiGianDat < date_add(curdate(), interval 1 day)
      and dv.TrangThaiThanhToan = 'Thanh Toán Thành Công'
    group by sc.MaPhong, TenPhong
    order by doanhthu desc
";
$resultRevenueByRoom = mysqli_query($conn, $sqlRevenueByRoom);
$revenueByRoom = [];
if ($resultRevenueByRoom) {
    while ($rowRoom = mysqli_fetch_assoc($resultRevenueByRoom)) {
        $revenueByRoom[] = $rowRoom;
    }
}

// 6.2 Theo rạp (nếu có bảng rapchieu)
$sqlRevenueByRap = "
    select 
        r.MaRap,
        r.TenRap,
        sum(dv.TongTien) as doanhthu,
        sum(dv.SoLuong) as sove
    from datve dv
    join suatchieu sc on dv.MaSuatChieu = sc.MaSuatChieu
    join phongchieu pc on sc.MaPhong = pc.MaPhong
    join rapchieu r on pc.MaRap = r.MaRap
    where dv.ThoiGianDat >= date_sub(curdate(), interval {$range} day)
      and dv.ThoiGianDat < date_add(curdate(), interval 1 day)
      and dv.TrangThaiThanhToan = 'Thanh Toán Thành Công'
    group by r.MaRap, r.TenRap
    order by doanhthu desc
";
$resultRevenueByRap = @mysqli_query($conn, $sqlRevenueByRap);
$revenueByTheater = [];
if ($resultRevenueByRap) {
    while ($rowRap = mysqli_fetch_assoc($resultRevenueByRap)) {
        $revenueByTheater[] = $rowRap;
    }
}

// ===================================================
// 7. Phân bổ doanh thu theo phương thức thanh toán
// ===================================================
$sqlPaymentDist = "
    select 
        PhuongThucThanhToan,
        sum(TongTien) as doanhthu,
        count(*) as so_giao_dich
    from datve
    where ThoiGianDat >= date_sub(curdate(), interval {$range} day)
      and ThoiGianDat < date_add(curdate(), interval 1 day)
      and TrangThaiThanhToan = 'Thanh Toán Thành Công'
    group by PhuongThucThanhToan
    order by doanhthu desc
";
$resultPaymentDist = mysqli_query($conn, $sqlPaymentDist);
$paymentDist = [];
if ($resultPaymentDist) {
    while ($rowPay = mysqli_fetch_assoc($resultPaymentDist)) {
        $paymentDist[] = $rowPay;
    }
}

// ===================================================================
// 8. Bảng chi tiết nhanh: suất chiếu (hôm nay + ngày mai) + trạng thái
// ===================================================================
$sqlUpcomingShows = "
    select 
        sc.MaSuatChieu,
        sc.MaPhong,
        sc.ThoiGianBatDau,
        sc.MaPhim,
        p.TenPhim,
        pc.TenPhong,
        pc.LoaiPhong
    from suatchieu sc
    join phim p on sc.MaPhim = p.MaPhim
    left join phongchieu pc on sc.MaPhong = pc.MaPhong
    where sc.ThoiGianBatDau >= curdate()
      and sc.ThoiGianBatDau < date_add(curdate(), interval 2 day)
    order by sc.ThoiGianBatDau asc
";
$resultUpcomingShows = mysqli_query($conn, $sqlUpcomingShows);

$upcomingShows = [];
if ($resultUpcomingShows) {
    while ($rowUpcoming = mysqli_fetch_assoc($resultUpcomingShows)) {
        $upcomingShows[] = $rowUpcoming;
    }
}

// Số ghế đã đặt cho các suất chiếu sắp tới (join trực tiếp)
$bookedUpcoming = [];
$sqlBookedUpcoming = "
    select 
        sc.MaSuatChieu,
        coalesce(sum(dv.SoLuong), 0) as DaDat
    from suatchieu sc
    left join datve dv
        on sc.MaSuatChieu = dv.MaSuatChieu
        and dv.TrangThaiThanhToan = 'Thanh Toán Thành Công'
    where sc.ThoiGianBatDau >= curdate()
      and sc.ThoiGianBatDau < date_add(curdate(), interval 2 day)
    group by sc.MaSuatChieu
";
$resultBookedUpcoming = mysqli_query($conn, $sqlBookedUpcoming);
if ($resultBookedUpcoming) {
    while ($rowBU = mysqli_fetch_assoc($resultBookedUpcoming)) {
        $bookedUpcoming[$rowBU['MaSuatChieu']] = (int)$rowBU['DaDat'];
    }
}

// Helper: tính trạng thái lấp đầy cho từng suất
function getSeatStatus($booked, $capacity)
{
    if ($capacity <= 0) return 'Chưa có dữ liệu';
    $ratio = $booked / $capacity;
    if ($ratio >= 0.9) return 'Đủ chỗ';
    if ($ratio >= 0.6) return 'Còn chỗ ít';
    return 'Còn nhiều chỗ';
}

ob_start();
?>

<style>
    /* Phong cách CGV tone đen - đỏ */
    .cgv-dashboard-header {
        background: #111;
        color: #fff;
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .cgv-dashboard-header h4 {
        margin: 0;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .cgv-dashboard-header .badge-range {
        background: #e71a0f;
        color: #fff;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
    }

    .kpi-card {
        background: #1b1b1b;
        border: 1px solid #333;
        color: #fff;
        border-radius: 10px;
        padding: 16px 18px;
        margin-bottom: 16px;
    }

    .kpi-title {
        font-size: 13px;
        text-transform: uppercase;
        opacity: 0.8;
    }

    .kpi-value {
        font-size: 22px;
        font-weight: 700;
        margin-top: 4px;
    }

    .kpi-sub {
        font-size: 12px;
        opacity: 0.7;
    }

    .cgv-card {
        background: #181818;
        border-radius: 10px;
        border: 1px solid #333;
        margin-bottom: 18px;
    }

    .cgv-card-header {
        padding: 12px 16px;
        border-bottom: 1px solid #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #fff;
        background: #202020;
    }

    .cgv-card-header h6 {
        margin: 0;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .cgv-card-body {
        padding: 14px 16px;
        color: #ddd;
    }

    .cgv-badge-status {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
    }

    .status-full {
        background: #e71a0f;
        color: #fff;
    }

    .status-low {
        background: #ff9800;
        color: #fff;
    }

    .status-many {
        background: #4caf50;
        color: #fff;
    }

    .table-dark-cgv {
        width: 100%;
        color: #ddd;
        font-size: 13px;
    }

    .table-dark-cgv thead {
        background: #202020;
    }

    .table-dark-cgv th,
    .table-dark-cgv td {
        padding: 8px 10px;
        white-space: nowrap;
        border-bottom: 1px solid #333;
    }
</style>

<div class="cgv-dashboard-header">
    <h4><i class="fas fa-chart-line me-2"></i>Tổng quan</h4>
    <div>
        <span class="badge-range me-2">Khoảng: <?= htmlspecialchars($rangeLabel) ?></span>
        <a href="index.php?module=dashboard&range=7" class="btn btn-sm btn-outline-light">7 ngày</a>
        <a href="index.php?module=dashboard&range=30" class="btn btn-sm btn-outline-light">30 ngày</a>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-title">Doanh thu hôm nay</div>
            <div class="kpi-value">
                <?= number_format($revenueToday, 0, ',', '.') ?> đ
            </div>
            <div class="kpi-sub">Ngày <?= date('d/m/Y') ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-title">Số vé bán hôm nay</div>
            <div class="kpi-value">
                <?= $ticketsToday ?>
            </div>
            <div class="kpi-sub">Vé đã thanh toán</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-title">Tỷ lệ lấp đầy ghế hôm nay</div>
            <div class="kpi-value">
                <?= $seatFillRateToday ?>%
            </div>
            <div class="kpi-sub">
                Đã đặt <?= $totalBookedToday ?>/<?= $totalCapacityToday ?> ghế (theo suất chiếu)
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-title">Số phim đang chiếu hôm nay</div>
            <div class="kpi-value">
                <?= $moviesNowShowing ?>
            </div>
            <div class="kpi-sub">Có suất chiếu trong ngày</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Biểu đồ xu hướng -->
    <div class="col-md-8">
        <div class="cgv-card">
            <div class="cgv-card-header">
                <h6><i class="fas fa-chart-area me-2"></i>Xu hướng doanh thu & số vé</h6>
            </div>
            <div class="cgv-card-body">
                <canvas id="chartRevenueTickets" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- Top phim -->
    <div class="col-md-4">
        <div class="cgv-card">
            <div class="cgv-card-header">
                <h6><i class="fas fa-film me-2"></i>Top phim theo doanh thu</h6>
            </div>
            <div class="cgv-card-body">
                <?php if (empty($topMovies)): ?>
                    <p class="mb-0">Chưa có dữ liệu trong khoảng thời gian này.</p>
                <?php else: ?>
                    <table class="table-dark-cgv">
                        <thead>
                            <tr>
                                <th>Phim</th>
                                <th class="text-end">Doanh thu</th>
                                <th class="text-end">Vé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topMovies as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['TenPhim']) ?></td>
                                    <td class="text-end">
                                        <?= number_format($m['doanhthu'], 0, ',', '.') ?> đ
                                    </td>
                                    <td class="text-end"><?= (int)$m['sove'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Phương thức thanh toán -->
        <div class="cgv-card">
            <div class="cgv-card-header">
                <h6><i class="fas fa-wallet me-2"></i>Phương thức thanh toán</h6>
            </div>
            <div class="cgv-card-body">
                <?php if (empty($paymentDist)): ?>
                    <p class="mb-0">Chưa có dữ liệu.</p>
                <?php else: ?>
                    <table class="table-dark-cgv">
                        <thead>
                            <tr>
                                <th>Phương thức</th>
                                <th class="text-end">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentDist as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['PhuongThucThanhToan']) ?></td>
                                    <td class="text-end">
                                        <?= number_format($p['doanhthu'], 0, ',', '.') ?> đ
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Theo phòng -->
    <div class="col-md-6">
        <div class="cgv-card">
            <div class="cgv-card-header">
                <h6><i class="fas fa-door-open me-2"></i>Phân bố doanh thu theo phòng</h6>
            </div>
            <div class="cgv-card-body">
                <?php if (empty($revenueByRoom)): ?>
                    <p class="mb-0">Chưa có dữ liệu.</p>
                <?php else: ?>
                    <table class="table-dark-cgv">
                        <thead>
                            <tr>
                                <th>Phòng</th>
                                <th class="text-end">Doanh thu</th>
                                <th class="text-end">Vé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueByRoom as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['TenPhong'] ?: $r['MaPhong']) ?></td>
                                    <td class="text-end">
                                        <?= number_format($r['doanhthu'], 0, ',', '.') ?> đ
                                    </td>
                                    <td class="text-end"><?= (int)$r['sove'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Theo rạp (nếu có) -->
    <div class="col-md-6">
        <div class="cgv-card">
            <div class="cgv-card-header">
                <h6><i class="fas fa-building me-2"></i>Phân bố doanh thu theo rạp</h6>
            </div>
            <div class="cgv-card-body">
                <?php if (empty($revenueByTheater)): ?>
                    <p class="mb-0">Chưa cấu hình bảng rạp hoặc chưa có dữ liệu.</p>
                <?php else: ?>
                    <table class="table-dark-cgv">
                        <thead>
                            <tr>
                                <th>Rạp</th>
                                <th class="text-end">Doanh thu</th>
                                <th class="text-end">Vé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueByTheater as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['TenRap']) ?></td>
                                    <td class="text-end">
                                        <?= number_format($r['doanhthu'], 0, ',', '.') ?> đ
                                    </td>
                                    <td class="text-end"><?= (int)$r['sove'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bảng suất chiếu sắp tới -->
<div class="cgv-card">
    <div class="cgv-card-header">
        <h6><i class="fas fa-clock me-2"></i>Suất chiếu hôm nay + ngày mai</h6>
    </div>
    <div class="cgv-card-body">
        <?php if (empty($upcomingShows)): ?>
            <p class="mb-0">Không có suất chiếu nào trong hôm nay và ngày mai.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-dark-cgv">
                    <thead>
                        <tr>
                            <th>Mã suất</th>
                            <th>Phim</th>
                            <th>Giờ bắt đầu</th>
                            <th>Phòng</th>
                            <th class="text-end">Ghế đã đặt / tổng</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingShows as $s): ?>
                            <?php
                            $maShow = $s['MaSuatChieu'];
                            $maPhong = $s['MaPhong'];
                            $capacity = isset($seatsPerRoom[$maPhong]) ? $seatsPerRoom[$maPhong] : 0;
                            $booked = isset($bookedUpcoming[$maShow]) ? $bookedUpcoming[$maShow] : 0;
                            $statusText = getSeatStatus($booked, $capacity);

                            $ratio = $capacity > 0 ? $booked / $capacity : 0;
                            $statusClass = 'status-many';
                            if ($ratio >= 0.9) $statusClass = 'status-full';
                            else if ($ratio >= 0.6) $statusClass = 'status-low';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($maShow) ?></td>
                                <td style="max-width: 260px; white-space: normal;">
                                    <?= htmlspecialchars($s['TenPhim']) ?>
                                </td>
                                <td><?= date('d/m H:i', strtotime($s['ThoiGianBatDau'])) ?></td>
                                <td><?= htmlspecialchars($s['TenPhong'] ?: $maPhong) ?></td>
                                <td class="text-end">
                                    <?= $booked ?>/<?= $capacity ?>
                                </td>
                                <td>
                                    <span class="cgv-badge-status <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartRevenue = <?= json_encode($chartRevenue) ?>;
    const chartTickets = <?= json_encode($chartTickets) ?>;

    const ctx = document.getElementById('chartRevenueTickets').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                    label: 'Doanh thu (đ)',
                    data: chartRevenue,
                    yAxisID: 'y1',
                    tension: 0.3,
                    borderWidth: 2
                },
                {
                    label: 'Số vé',
                    data: chartTickets,
                    yAxisID: 'y2',
                    tension: 0.3,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#ddd'
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#aaa'
                    },
                    grid: {
                        color: '#333'
                    }
                },
                y1: {
                    position: 'left',
                    ticks: {
                        color: '#ddd'
                    },
                    grid: {
                        color: '#333'
                    }
                },
                y2: {
                    position: 'right',
                    ticks: {
                        color: '#aaa'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/master.php';
