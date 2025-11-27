<!-- admin/layouts/master.php -->
<?php
// $title  -> tiêu đề trang (do trang con set)
// $content -> nội dung body (do trang con đổ vào)
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= isset($title) ? htmlspecialchars($title) : 'Admin Panel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/css/admin.css">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <style>
        .cgv-modal .modal-dialog {
            max-width: 420px;
        }

        .cgv-modal-content {
            background-color: #111;
            border-radius: 12px;
            border: 1px solid #222;
            color: #fff;
        }

        .cgv-modal .modal-header {
            background: linear-gradient(90deg, #e71a0f, #b70d06);
            border-bottom: 1px solid #222;
        }

        .cgv-modal .modal-body {
            background-color: #111;
        }

        .cgv-modal .modal-footer {
            background-color: #111;
        }

        .cgv-btn-danger {
            background-color: #e71a0f;
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 999px;
        }

        .cgv-btn-danger:hover {
            background-color: #ff3b2f;
        }
    </style>

</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div id="layoutSidenav">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>
        <div id="layoutSidenav_content">

            <?= $content ?? '' ?>

            <?php include __DIR__ . '/partials/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= ADMIN_URL ?>assets/js/admin.js"></script>
</body>

</html>