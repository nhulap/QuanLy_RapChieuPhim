<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Đăng ký tài khoản</title>
</head>

<body>
    <h1>Tạo tài khoản</h1>
    <div class="container_register">
        <form action="index.php" method="post" id="form_register">
            <div>
                <input type="text" placeholder="Tên đăng nhập">
            </div>

            <div>
                <input type="password" placeholder="Mật khẩu">
            </div>
            <div>
                <input type="password" placeholder="Nhập lại mật khẩu">
            </div>

            <div>
                <input type="tel" placeholder="Số điện thoại">
            </div>
            <div>
                <input type="Email" placeholder="Email">
            </div>
            <div id="submit_regis">
                <input type="submit" value="Đăng ký">
                <div id="btn_dn">
                    <p>Đã có tài khoản?</p>
                    <input type="text" value="Đăng nhập">
                </div>
            </div>
        </form>
    </div>
</body>

</html>