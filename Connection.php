<?php
// 1. Ket noi CSDL Kieu thu tuc
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quanly_rapchieuphim";

$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>