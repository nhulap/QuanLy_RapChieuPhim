<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Vé Thành Công</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container-success {
            background-color: #fff;
            border-radius: 10px;
            padding: 40px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        h1 {
            color: #d11e3b; 
            margin-bottom: 20px;
        }

        .success-msg {
            color: #28a745; 
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 25px;
        }

        a.home-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #d11e3b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
        }

        a.home-link:hover {
            background-color: #a3182d;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container-success {
                padding: 30px 20px;
            }
            h1 {
                font-size: 24px;
            }
            .success-msg {
                font-size: 1.1em;
            }
        }

        @media (max-width: 480px) {
            .container-success {
                padding: 20px 15px;
            }
            h1 {
                font-size: 20px;
            }
            .success-msg {
                font-size: 1em;
            }
            a.home-link {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container-success">
        <h1>🎉 Đặt Vé Thành Công!</h1>
        <p class="success-msg">
            <?php echo htmlspecialchars($_GET['msg'] ?? 'Vé của bạn đã được đặt.'); ?>
        </p>
        <p>
            <a href="../index.php" class="home-link">Quay về trang chủ</a>
        </p>
    </div>
</body>
</html>
