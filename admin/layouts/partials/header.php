<?php
// master.php hoặc header.php

// require __DIR__ . '/../../Connection.php';  // nếu cần

// =============================
// Đếm số đặt phòng VIP đang chờ xác nhận
// =============================
$pendingVipCount = 0;

$sql_pending_vip = "
    select count(*) as pending_count
    from datphongthue
    where TrangThaiXacNhan = 'Pending'
";

$result_pending_vip = mysqli_query($conn, $sql_pending_vip);
if ($result_pending_vip && mysqli_num_rows($result_pending_vip) > 0) {
    $row_pending_vip = mysqli_fetch_assoc($result_pending_vip);
    $pendingVipCount = (int)$row_pending_vip['pending_count'];
}
?>

<!-- Có thể đặt block style này trong master.css nếu muốn -->
<style>
    .cgv-vip-banner {
        background: #e71a0f;
        color: #ffd54f;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;

        /* ép nằm đúng một hàng */
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        gap: 6px;
        text-decoration: none;
    }

    .cgv-vip-banner i {
        font-size: 14px;
        display: inline-block;
    }

    .cgv-vip-banner:hover {
        background: #b31209;
        color: #ffffff;
        text-decoration: none;
    }

    .cgv-noti-dropdown {
        min-width: 260px;
        background: #181818;
        color: #eee;
        border: 1px solid #333;
        box-shadow: 0 8px 18px rgba(0, 0, 0, .7);
        padding: 0;
    }

    .cgv-noti-dropdown li {
        list-style: none;
    }

    .cgv-noti-title {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #ffcc4d;
    }

    .cgv-noti-text {
        font-size: 13px;
    }

    .cgv-noti-empty {
        font-size: 13px;
        color: #aaa;
        text-align: center;
    }

    .cgv-noti-btn {
        background: #e71a0f;
        border: none;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .cgv-noti-btn:hover {
        background: #b31209;
    }
</style>


<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark" style="padding-top: 20px">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="index.php?module=dashboard&action=index" style=" margin-top:25px; margin-left: 15px">
        <img src="<?php echo USER_URL . 'Images/cinema-logo.png' ?>" alt="" width="70">
    </a>

    <!-- Nút toggle sidebar -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <?php
    $admin_name = $_SESSION['user'] ?? 'Admin';
    ?>

    <!-- Banner xem đơn đặt phòng -->
    <div class="ms-3 d-none d-md-block">
        <a href="index.php?module=movietheater&action=pending" class="cgv-vip-banner">
            <i class="fas fa-door-open"></i>
            xem đơn đặt phòng hiện có
        </a>
    </div>

    <!-- Đẩy phần user + chuông sang bên phải -->
    <div class="ms-auto d-flex align-items-center">
        <div style="margin-left:5px">
            <p class="mb-0 me-3" style="color: white;  display: inline-flex;
                                        align-items: center;
                                        white-space: nowrap;
                                        gap: 6px;">Hello,
                <?php echo htmlspecialchars($admin_name); ?></p>

        </div>

        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <!-- Dropdown user -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown"
                    href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">


                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </li>

            <!-- Chuông thông báo đặt phòng VIP -->
            <li class="nav-item dropdown">
                <a class="nav-link position-relative"
                    href="#"
                    id="vipNotiDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="Đặt phòng VIP chờ xác nhận">
                    <i class="fas fa-bell"></i>
                    <?php if ($pendingVipCount > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                            <?= $pendingVipCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <ul class="dropdown-menu dropdown-menu-end cgv-noti-dropdown" aria-labelledby="vipNotiDropdown">
                    <?php if ($pendingVipCount > 0): ?>
                        <li class="px-3 pt-2 pb-3">
                            <div class="cgv-noti-title mb-1">
                                <i class="fas fa-door-open me-1 text-danger"></i> Đặt phòng VIP
                            </div>
                            <div class="cgv-noti-text mb-2">
                                Có <strong><?= $pendingVipCount ?></strong> thông báo xác nhận đặt phòng đang chờ.
                            </div>
                            <a href="index.php?module=movietheater&action=pending"
                                class="btn btn-sm w-100 cgv-noti-btn">
                                Xem danh sách chờ xác nhận
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="px-3 py-3">
                            <div class="cgv-noti-empty">
                                Không có thông báo nào!
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        </ul>
    </div>
</nav>