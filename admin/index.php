<?php
//  admin/index.php
require_once __DIR__ . '/../config/config.php';

// require_once __DIR__ . '/core/db.php';
// require_once __DIR__ . '/core/auth.php';
// require_once __DIR__ . '/core/helpers.php';

// kiểm tra login admin
//check_admin_login(); // tự viết trong auth.php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_id'] != 'ADMIN') {
    header("Location: ../Login&Register/Login.php");
    exit();
}

// Lấy module & action từ query string
$module = isset($_GET['module']) ? $_GET['module'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
require_once __DIR__ . '/../Connection.php';
require_once __DIR__ . '/router.php';
