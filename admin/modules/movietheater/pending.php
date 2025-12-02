<?php
// admin/modules/datphongthue/pending.php

$title = 'Đặt phòng VIP chờ xác nhận';

ob_start();

// Lấy danh sách các đặt phòng đang chờ xác nhận
$sql_pending_list = "
    select 
        dp.MaDatPhong,
        dp.MaKhachHang,
        kh.HoTen,
        dp.MaPhong,
        pc.TenPhong,
        r.TenRap,
        dp.MaPhim,
        p.TenPhim,
        dp.ThoiGianBatDau,
        dp.ThoiGianKetThuc,
        dp.TongTienThue,
        dp.MucDichThue,
        dp.TrangThaiXacNhan,
        dp.TrangThaiThanhToan,
        dp.NgayDat
    from datphongthue dp
    join khachhang kh on dp.MaKhachHang = kh.MaKhachHang
    join phongchieu pc on dp.MaPhong = pc.MaPhong
    join rapchieu r on pc.MaRap = r.MaRap
    left join phim p on dp.MaPhim = p.MaPhim
    where dp.TrangThaiXacNhan = 'Pending'
    order by dp.NgayDat desc
";

$result_pending_list = mysqli_query($conn, $sql_pending_list);
?>

<div class="card cgv-card shadow-sm mb-4">
    <div class="card-header cgv-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-door-open me-2"></i> Đặt phòng VIP chờ xác nhận
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 cgv-table">
                <thead>
                    <tr>
                        <th>Mã đặt phòng</th>
                        <th>Khách hàng</th>
                        <th>Rạp / Phòng</th>
                        <th>Phim (nếu có)</th>
                        <th>Thời gian</th>
                        <th>Mục đích</th>
                        <th class="text-end">Tổng tiền</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_pending_list && mysqli_num_rows($result_pending_list) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_pending_list)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['MaDatPhong']) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['HoTen']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['MaKhachHang']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['TenRap']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['TenPhong']) ?></small>
                                </td>
                                <td>
                                    <?php if ($row['TenPhim']): ?>
                                        <?= htmlspecialchars($row['TenPhim']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Không chiếu phim</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($row['ThoiGianBatDau'])) ?>
                                    <br>
                                    <span class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($row['ThoiGianKetThuc'])) ?>
                                    </span>
                                </td>
                                <td style="max-width: 200px; white-space: normal;">
                                    <?= htmlspecialchars($row['MucDichThue']) ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($row['TongTienThue'], 0, ',', '.') ?> đ
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success btn-approve-vip"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalConfirmVip"
                                        data-id="<?= htmlspecialchars($row['MaDatPhong']) ?>"
                                        data-name="<?= htmlspecialchars($row['HoTen']) ?>"
                                        data-room="<?= htmlspecialchars($row['TenPhong']) ?>"
                                        data-action="approve">
                                        <i class="fas fa-check"></i> Đồng ý
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger btn-reject-vip"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalConfirmVip"
                                        data-id="<?= htmlspecialchars($row['MaDatPhong']) ?>"
                                        data-name="<?= htmlspecialchars($row['HoTen']) ?>"
                                        data-room="<?= htmlspecialchars($row['TenPhong']) ?>"
                                        data-action="reject">
                                        <i class="fas fa-times"></i> Từ chối
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-3">
                                Hiện chưa có yêu cầu đặt phòng VIP nào đang chờ xác nhận.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL XÁC NHẬN ĐẶT PHÒNG VIP - STYLE CGV -->
<div class="modal fade cgv-modal" id="modalConfirmVip" tabindex="-1" aria-labelledby="modalConfirmVipLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgv-modal-content">

            <!-- Header -->
            <div class="modal-header border-0">
                <h5 class="modal-title text-light" id="modalConfirmVipLabel">
                    <i class="fas fa-door-open me-2 text-danger"></i> Xác nhận đặt phòng VIP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <p class="mb-2 text-light">
                    Bạn có chắc chắn muốn thực hiện thao tác sau đối với yêu cầu thuê phòng:
                </p>

                <p class="fw-bold text-warning mb-1" id="vipConfirmRoom">Tên phòng</p>
                <p class="fw-bold text-info mb-3" id="vipConfirmCustomer">Tên khách hàng</p>

                <!-- Các input sẽ nằm TRONG form ở footer, không cần đặt ở đây -->
                <div id="block-reason" style="display:none;">
                    <label class="text-secondary small mb-1">Lý do từ chối (không bắt buộc)</label>
                    <textarea
                        class="form-control bg-dark text-light border-secondary"
                        name="LyDoTuChoi"
                        id="vip-ly-do-tu-choi"
                        rows="3"
                        placeholder="Ví dụ: Trùng lịch với sự kiện nội bộ, phòng đang bảo trì, ..."></textarea>
                </div>

                <p class="text-secondary small mb-0 mt-3" id="vipConfirmWarning">
                    Thao tác này sẽ cập nhật trạng thái yêu cầu trên hệ thống.
                </p>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">
                    Hủy
                </button>

                <!-- FORM GỬI LÊN update_status -->
                <form method="post" action="index.php?module=movietheater&action=updatestatus" class="m-0" id="vipConfirmForm">
                    <input type="hidden" name="MaDatPhong" id="vip-ma-dat-phong">
                    <input type="hidden" name="TrangThaiXacNhan" id="vip-trang-thai-xn">
                    <input type="hidden" name="LyDoTuChoi" id="vip-ly-do-tu-choi-hidden">

                    <button type="submit" class="btn cgv-btn-danger px-3" id="vipConfirmBtn">
                        <i class="fas fa-check-circle me-1"></i> Xác nhận
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('modalConfirmVip');
        const roomEl = document.getElementById('vipConfirmRoom');
        const customerEl = document.getElementById('vipConfirmCustomer');
        const maInput = document.getElementById('vip-ma-dat-phong');
        const statusInput = document.getElementById('vip-trang-thai-xn');
        const reasonBlock = document.getElementById('block-reason');
        const reasonTextarea = document.getElementById('vip-ly-do-tu-choi');
        const reasonHidden = document.getElementById('vip-ly-do-tu-choi-hidden');
        const confirmBtn = document.getElementById('vipConfirmBtn');
        const warningEl = document.getElementById('vipConfirmWarning');

        modalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const ma = button.getAttribute('data-id');
            const kh = button.getAttribute('data-name');
            const phong = button.getAttribute('data-room');
            const action = button.getAttribute('data-action'); // approve | reject

            maInput.value = ma;
            roomEl.textContent = phong;
            customerEl.textContent = kh;

            // Đảm bảo đồng bộ lý do từ chối vào hidden trước khi submit
            reasonTextarea.addEventListener('input', function() {
                reasonHidden.value = reasonTextarea.value;
            });

            if (action === 'approve') {
                statusInput.value = 'Approved';

                confirmBtn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Đồng ý';
                confirmBtn.classList.remove('btn-danger');
                confirmBtn.classList.add('btn-success');

                reasonBlock.style.display = 'none';
                reasonTextarea.value = '';
                reasonHidden.value = '';
                warningEl.textContent = 'Sau khi xác nhận, yêu cầu sẽ được chuyển sang trạng thái Đã duyệt.';
            } else {
                statusInput.value = 'Rejected';

                confirmBtn.innerHTML = '<i class="fas fa-times-circle me-1"></i> Từ chối';
                confirmBtn.classList.remove('btn-success');
                confirmBtn.classList.add('btn-danger');

                reasonBlock.style.display = 'block';
                warningEl.textContent = 'Sau khi từ chối, CSKH nên thông báo lý do cho khách hàng.';
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/master.php';
