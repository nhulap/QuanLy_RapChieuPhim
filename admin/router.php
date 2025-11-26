<?php
// admin/router.php

$module = preg_replace('/[^a-zA-Z0-9_]/', '', $module);
$action = preg_replace('/[^a-zA-Z0-9_]/', '', $action);

$path = __DIR__ . "/modules/{$module}/{$action}.php";

if (file_exists($path)) {
    require $path;
} else {
    http_response_code(404);
    $title = 'Không tìm thấy trang';
    ob_start();
?>
    <h2>404 - Không tìm thấy trang</h2>
    <p>Module: <?= htmlspecialchars($module) ?>, Action: <?= htmlspecialchars($action) ?></p>
<?php
    $content = ob_get_clean();
    include __DIR__ . '/layouts/master.php';
}
