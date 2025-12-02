<?php
// admin/modules/datphongthue/update_status.php
session_start();

$ma_dat_phong = $_POST['MaDatPhong'] ?? '';
$trang_thai_xn = $_POST['TrangThaiXacNhan'] ?? '';
$ly_do_tu_choi = $_POST['LyDoTuChoi'] ?? '';

$allowed_status = ['Approved', 'Rejected'];

if ($ma_dat_phong === '' || !in_array($trang_thai_xn, $allowed_status, true)) {
    header('Location: index.php?module=datphongthue&action=pending&msg=invalid');
    exit;
}

$ma_dat_phong_safe = mysqli_real_escape_string($conn, $ma_dat_phong);
$trang_thai_xn_safe = mysqli_real_escape_string($conn, $trang_thai_xn);
$ly_do_tu_choi_safe = mysqli_real_escape_string($conn, $ly_do_tu_choi);

// Tạm thời chỉ update trạng thái, nếu sau này có cột LyDoTuChoi thì thêm vào set
$sql_update = "
    update datphongthue
    set TrangThaiXacNhan = '{$trang_thai_xn_safe}'
    where MaDatPhong = '{$ma_dat_phong_safe}'
";

mysqli_query($conn, $sql_update);

header('Location: index.php?module=movietheater&action=pending&msg=success');
exit;
