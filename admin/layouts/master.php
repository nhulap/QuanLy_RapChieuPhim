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
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div id="layoutSidenav">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <?= $content ?? '' ?>
            </main>
            <?php include __DIR__ . '/partials/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= ADMIN_URL ?>assets/js/admin.js"></script>
</body>

</html>