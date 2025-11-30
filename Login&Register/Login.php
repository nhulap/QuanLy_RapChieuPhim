<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Đăng nhập tài khoản</title>
</head>

<body>
    <?php
    session_start();
    include "../Connection.php";
    require_once __DIR__ . '/../config/config.php'; 
    require_once __DIR__ . '/../layout/header.php'; 
    $messError = '';

    if ($_SERVER["REQUEST_METHOD"] == 'POST') {

        if (isset($_POST['nut_dn'])) {

            $name = $_POST['name'];
            $pass = $_POST['pass'];

            $sql_check = "SELECT * FROM khachhang 
                      WHERE HoTen = '$name' OR Email = '$name' 
                      LIMIT 1";

            $result = mysqli_query($conn, $sql_check);

            if ($result && mysqli_num_rows($result) === 1) {

                $row = mysqli_fetch_assoc($result);

                if ($row['MatKhau'] === $pass) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user'] = $row['HoTen'];
                    $_SESSION['user_id'] = $row['MaKhachHang'];

                    if ($row['MaKhachHang'] === 'ADMIN') {
                        header("Location: ../admin/index.php");
                        exit();
                    }

                    header("Location: ../index.php");
                    exit();
                } else {
                    $messError = "Sai mật khẩu!";
                }
            } else {
                $messError = "Tài khoản không tồn tại!";
            }
        }

        if (isset($_POST['nut_dk'])) {
            header("Location: Register.php");
            exit();
        }
    }
    ?>

    <h1>Đăng nhập tài khoản</h1>
    <div class="container_register">
        <form action="" method="post" id="form_login">
            <div>
                <input type="text" name="name" placeholder="Tên tài khoản hoặc email"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div>
                <input type="password" name="pass" placeholder="Mật khẩu">
            </div>
            <div id="submit_regis">
                <input type="submit" name="nut_dn" value="Đăng nhập">
                <div id="btn_dn">
                    <p>Chưa có tài khoản?</p>
                    <input type="submit" name="nut_dk" value="Đăng ký">
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