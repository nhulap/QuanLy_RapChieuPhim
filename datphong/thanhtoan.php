<?php
session_start();
require "../Connection.php"; // Kết nối database

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    // Lưu lại URL hiện tại (bao gồm cả tham số GET) để chuyển hướng quay lại sau khi đăng nhập
    $_SESSION['redirect_url'] = '../datphong/thanh_toan.php?' . http_build_query($_GET);
    header("Location: ../Login&Register/Login.php");
    exit;
}

// 1. Lấy mã đơn hàng từ URL (MaDatPhong)
$order_id = $_GET['order'] ?? '';

if (empty($order_id) || !preg_match('/^DP\d{8}$/', $order_id)) {
    die("Lỗi: Mã đơn hàng không hợp lệ. Vui lòng quay lại.");
}

// Làm sạch mã đơn hàng
$order_id_safe = mysqli_real_escape_string($conn, $order_id);
$ma_khach_hang_hien_tai = $_SESSION['user_id'];
$order_data = null;

// 2. Truy vấn chi tiết đơn Đặt Phòng Thuê
// Đã thêm dpt.MaPhim, dpt.MucDichThue, dpt.TrangThaiXacNhan và LEFT JOIN với phim
$sql_query = "
    SELECT 
        dpt.MaDatPhong, 
        dpt.MaPhong, 
        dpt.MaPhim, 
        dpt.MucDichThue, 
        dpt.ThoiGianBatDau, 
        dpt.ThoiGianKetThuc, 
        dpt.TongTienThue,
        dpt.TrangThaiThanhToan,
        dpt.TrangThaiXacNhan,
        p.TenPhong,
        r.TenRap,
        pf.TenPhim 
    FROM datphongthue dpt
    JOIN phong p ON dpt.MaPhong = p.MaPhong
    JOIN rap r ON p.MaRap = r.MaRap
    LEFT JOIN phim pf ON dpt.MaPhim = pf.MaPhim -- LEFT JOIN để lấy tên phim (nếu MaPhim không NULL)
    WHERE dpt.MaDatPhong = '$order_id_safe' 
    AND dpt.MaKhachHang = '$ma_khach_hang_hien_tai'
    LIMIT 1";

$result = mysqli_query($conn, $sql_query);

if ($result && mysqli_num_rows($result) > 0) {
    $order_data = mysqli_fetch_assoc($result);
} else {
    // Nếu không tìm thấy đơn hoặc không phải của khách hàng này
    die("Lỗi: Không tìm thấy đơn đặt phòng hoặc bạn không có quyền truy cập đơn hàng này.");
}

// Chuẩn bị dữ liệu hiển thị
$ma_phong = $order_data['MaPhong'];
$ten_phong = $order_data['TenPhong'];
$ten_rap = $order_data['TenRap'];
$thoi_gian_bat_dau = date('H:i | d/m/Y', strtotime($order_data['ThoiGianBatDau']));
$thoi_gian_ket_thuc = date('H:i | d/m/Y', strtotime($order_data['ThoiGianKetThuc']));
$tong_tien_thue = number_format($order_data['TongTienThue'], 0, ',', '.') . ' VND';
$trang_thai_thanh_toan = $order_data['TrangThaiThanhToan'];
$trang_thai_xac_nhan = $order_data['TrangThaiXacNhan'];
$muc_dich_thue = $order_data['MucDichThue'];
$ten_phim = $order_data['TenPhim'] ?? 'Không xem phim';

// Giả lập thông tin thanh toán (bạn cần thay thế bằng thông tin thật)
$bank_name = "NGÂN HÀNG ABC";
$account_number = "0123 4567 8901";
$account_name = "CONG TY TNHH RAP CHIEU PHIM";
// Nội dung chuyển khoản mặc định: TT + MaDatPhong
$transfer_content = "TT " . $order_data['MaDatPhong'];

mysqli_close($conn);

// Thiết lập hiển thị trạng thái
$status_color = ($trang_thai_xac_nhan === 'Approved') ? 'text-green-600' : 'text-orange-600';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Đặt Phòng VIP - <?php echo $order_id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f7f7;
        }
        .container {
            max-width: 900px;
        }
        .info-card {
            border-left: 5px solid #d91c5c; /* Màu đỏ đậm cho thanh sidebar */
        }
        .qr-placeholder {
            min-height: 250px;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #333;
            border: 1px dashed #aaa;
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="container mx-auto bg-white shadow-xl rounded-xl p-6 md:p-10">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-extrabold text-[#d91c5c] mb-2">
                <i class="fas fa-credit-card mr-2"></i> XÁC NHẬN & THANH TOÁN
            </h1>
            <p class="text-gray-600">Đơn Đặt Phòng Thuê VIP: <span class="font-bold text-lg text-blue-600"><?php echo $order_id; ?></span></p>
        </div>

        <?php if ($order_data['TrangThaiThanhToan'] === 'DaThanhToan'): ?>
            <!-- Đã Thanh Toán -->
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
                <p class="font-bold text-xl"><i class="fas fa-check-circle mr-2"></i> ĐƠN HÀNG ĐÃ ĐƯỢC THANH TOÁN</p>
                <p>Cảm ơn bạn. Vui lòng chờ Ban Quản Lý xác nhận cuối cùng về thời gian thuê.</p>
                <div class="mt-4"><a href="../Index.php" class="text-green-800 font-semibold hover:underline">Quay lại Trang Chủ</a></div>
            </div>
        <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Cột 1: Thông tin chi tiết đơn hàng -->
            <div class="lg:col-span-1">
                <h3 class="text-2xl font-semibold mb-4 text-gray-800 border-b pb-2">Chi Tiết Đơn Hàng</h3>
                <div class="space-y-4 info-card p-4 rounded-lg bg-gray-50">
                    
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="font-medium text-gray-600"><i class="fas fa-theater-mask mr-2"></i> Rạp:</span>
                        <span class="font-semibold text-gray-900"><?php echo $ten_rap; ?></span>
                    </div>

                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="font-medium text-gray-600"><i class="fas fa-door-open mr-2"></i> Phòng VIP:</span>
                        <span class="font-semibold text-gray-900"><?php echo $ten_phong; ?> (<?php echo $ma_phong; ?>)</span>
                    </div>

                    <!-- THÊM: Mục Đích Thuê -->
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="font-medium text-gray-600"><i class="fas fa-tags mr-2"></i> Mục đích:</span>
                        <span class="font-semibold text-gray-900"><?php echo $muc_dich_thue; ?></span>
                    </div>

                    <!-- THÊM: Phim (Nếu có) -->
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="font-medium text-gray-600"><i class="fas fa-film mr-2"></i> Phim:</span>
                        <span class="font-semibold text-gray-900"><?php echo $ten_phim; ?></span>
                    </div>
                    
                    <div class="border-b pb-2">
                        <p class="font-medium text-gray-600 mb-1"><i class="far fa-clock mr-2"></i> Bắt đầu:</p>
                        <p class="font-bold text-lg text-[#3b82f6]"><?php echo $thoi_gian_bat_dau; ?></p>
                    </div>

                    <div class="border-b pb-2">
                        <p class="font-medium text-gray-600 mb-1"><i class="far fa-calendar-alt mr-2"></i> Kết thúc:</p>
                        <p class="font-bold text-lg text-[#ef4444]"><?php echo $thoi_gian_ket_thuc; ?></p>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-gray-300">
                        <p class="text-md font-bold text-gray-800">Trạng thái Xác nhận:</p>
                        <p class="text-md font-extrabold <?php echo $status_color; ?>"><?php echo $trang_thai_xac_nhan; ?></p>
                    </div>

                    <div class="pt-4 border-t-2 border-dashed border-gray-300">
                        <p class="text-xl font-bold text-gray-800 mb-1">TỔNG TIỀN THANH TOÁN:</p>
                        <p class="text-3xl font-extrabold text-[#d91c5c]"><?php echo $tong_tien_thue; ?></p>
                    </div>
                </div>
            </div>

            <!-- Cột 2 & 3: Thông tin thanh toán (QR Code & Hướng dẫn) -->
            <div class="lg:col-span-2">
                <h3 class="text-2xl font-semibold mb-4 text-gray-800 border-b pb-2">Thanh Toán Chuyển Khoản</h3>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 rounded-lg">
                    <p class="font-bold"><i class="fas fa-exclamation-triangle mr-2"></i> Lưu ý quan trọng:</p>
                    <ul class="list-disc list-inside mt-2 text-sm">
                        <li>Vui lòng chuyển khoản trong vòng **30 phút** để ưu tiên giữ chỗ.</li>
                        <li>Nội dung chuyển khoản **bắt buộc** phải ghi rõ mã đơn hàng của bạn.</li>
                        <li>Trạng thái đơn hàng hiện tại: Xác nhận <span class="font-bold <?php echo $status_color; ?>"><?php echo $trang_thai_xac_nhan; ?></span>, Thanh toán <span class="font-bold text-orange-600">Chưa Thanh Toán</span>.</li>
                    </ul>
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Mã QR -->
                    <div class="md:w-1/3">
                        <div class="qr-placeholder rounded-lg shadow-md">
                                                        <!-- Thay thế bằng mã QR thật của ngân hàng/MoMo -->
                        </div>
                        <p class="text-center mt-2 text-sm text-gray-500">Quét mã QR để thanh toán nhanh</p>
                    </div>

                    <!-- Thông tin chi tiết -->
                    <div class="md:w-2/3 bg-white p-4 rounded-lg border shadow-sm">
                        <h4 class="text-xl font-bold text-gray-800 mb-4">Thông tin chuyển khoản</h4>
                        <div class="space-y-3">
                            <div class="p-3 bg-blue-50 rounded-md">
                                <p class="text-sm text-gray-600">Ngân hàng:</p>
                                <p class="font-bold text-blue-700 text-lg"><?php echo $bank_name; ?></p>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-md">
                                <p class="text-sm text-gray-600">Số tài khoản:</p>
                                <p class="font-bold text-blue-700 text-xl"><?php echo $account_number; ?></p>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-md">
                                <p class="text-sm text-gray-600">Chủ tài khoản:</p>
                                <p class="font-bold text-blue-700 text-lg"><?php echo $account_name; ?></p>
                            </div>
                            <div class="p-3 bg-red-100 rounded-md border-2 border-red-300">
                                <p class="text-sm text-red-600 font-bold">NỘI DUNG CHUYỂN KHOẢN (BẮT BUỘC):</p>
                                <p id="transferContent" class="font-extrabold text-red-800 text-2xl cursor-pointer" onclick="copyToClipboard('<?php echo $transfer_content; ?>')">
                                    <?php echo $transfer_content; ?> 
                                    <i class="fas fa-copy ml-2 text-base text-gray-500 hover:text-red-600"></i>
                                </p>
                                <p class="text-xs text-red-600 mt-1">Nhấn vào nội dung để sao chép</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer buttons -->
                <div class="mt-8 flex justify-center space-x-4">
                    <a href="../Index.php" class="bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-300 transition duration-150">
                        <i class="fas fa-home mr-2"></i> Quay lại Trang Chủ
                    </a>
                </div>
            </div>
            
        </div>
        <?php endif; ?>

    </div>

    <!-- Script sao chép nội dung -->
    <script>
        // Lưu ý: Đã thay thế alert() bằng console.log() và thông báo nhẹ (vì alert không hoạt động tốt trong iframe)
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                // Sử dụng thông báo tùy chỉnh thay cho alert()
                showToast("Đã sao chép nội dung chuyển khoản: " + text, 'success');
            } catch (err) {
                showToast("Lỗi: Không thể sao chép nội dung.", 'error');
                console.error('Không thể sao chép', err);
            }
            
            document.body.removeChild(textarea);
        }

        function showToast(message, type) {
            // Tạo một div thông báo đơn giản (thay thế alert)
            let toast = document.getElementById('custom-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'custom-toast';
                toast.style.cssText = `
                    position: fixed; top: 20px; right: 20px; padding: 10px 20px; 
                    border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
                    color: white; z-index: 1000; transition: opacity 0.3s, transform 0.3s;
                    opacity: 0; transform: translateY(-20px);
                `;
                document.body.appendChild(toast);
            }
            
            if (type === 'success') {
                toast.style.backgroundColor = '#4CAF50';
            } else if (type === 'error') {
                toast.style.backgroundColor = '#f44336';
            } else {
                toast.style.backgroundColor = '#333';
            }

            toast.innerHTML = message;
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
            }, 3000);
        }

    </script>
</body>
</html>