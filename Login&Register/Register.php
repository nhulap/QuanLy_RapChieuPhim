<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Đăng ký tài khoản</title>
</head>

<body>
    <?php
    include "../Connection.php"; // Nếu lỗi thay thành Connection.php hoặc ./Connection.php tùy theo file đường dẫn
    session_start();
    $messError = '';
    require_once __DIR__ . '/../config/config.php'; 
    require_once __DIR__ . '/../layout/header.php'; 
    if ($_SERVER["REQUEST_METHOD"] == 'POST') {

        $name  = $_POST['name']  ?? '';
        $pass  = $_POST['pass']  ?? '';
        $repass = $_POST['repass'] ?? '';
        $sdt   = $_POST['sdt']   ?? '';
        $email = $_POST['email'] ?? '';

        if (empty($name) || empty($email) || empty($pass)) {
            $messError = "Vui lòng nhập đầy đủ thông tin!";
        } else if ($pass !== $repass) {
            $messError = "Mật khẩu không trùng khớp!";
        } else {
            $sql_check = "SELECT * FROM khachhang WHERE Email = '$email' OR HoTen = '$name' LIMIT 1";
            $result_check = mysqli_query($conn, $sql_check);

            if (mysqli_num_rows($result_check) > 0) {
                $messError = "Email hoặc tên đăng nhập đã tồn tại!";
            }
        }

        if ($messError === '') {
            $sql_getId = "SELECT MAX(MaKhachHang) AS maKH FROM khachhang";
            $resultId = $conn->query($sql_getId);
            $rowId = $resultId->fetch_assoc();

            $num = (int)substr($rowId['maKH'], 2); 
            $Id = "KH" . str_pad($num + 1, 4, "0", STR_PAD_LEFT); // KH1001 : substr cắt bỏ 'KH' và chuyển 1001 sang số nguyên

            $sql = "INSERT INTO khachhang (MaKhachHang, HoTen, Email, SoDienThoai, MatKhau)
                VALUES ('$Id', '$name', '$email', '$sdt', '$pass')";

            if ($conn->query($sql) === TRUE) {
                $_SESSION['user'] = $name;
                $_SESSION['user_id'] = $Id;
                header("Location: ../Index.php");
                exit();
            } else {
                $messError = "Lỗi!";
            }
        }

        if (isset($_POST['nut_dn'])) {
            header('Location:Login.php');
            exit();
        }
    }
    ?>

    <h1>Tạo tài khoản</h1>
    <div class="container_register">
        <form action="" method="post" id="form_register">
            <div>
                <input type="text" name="name" placeholder="Tên đăng nhập" value="<?php echo $_POST['name'] ?? '' ?>">
            </div>

            <div>
                <input type="password" name="pass" placeholder="Mật khẩu">
            </div>
            <div>
                <input type="password" name="repass" placeholder="Xác nhận lại mật khẩu">
            </div>

            <div>
                <input type="tel" name="sdt" placeholder="Số điện thoại" value="<?php echo $_POST['sdt'] ?? '' ?>">
            </div>
            <div>
                <input type="Email" name="email" placeholder="Email" value="<?php echo $_POST['email'] ?? '' ?>">
            </div>
            <div id="submit_regis">
                <input type="submit" name="nut_dk" value="Đăng ký">
                <div id="btn_dn">
                    <p>Đã có tài khoản?</p>
                    <input type="submit" name="nut_dn" value="Đăng nhập">
                </div>
            </div>
        </form>

    </div>

    <?php
    if (!empty($messError)) {
        echo "<h2 style='color:red; margin-top:2%'>" . htmlspecialchars($messError) . "</h2>";
    }
    ?>

</body>

</html>