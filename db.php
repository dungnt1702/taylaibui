<?php
// Cấu hình database cho localhost
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tay99672_qlss');

// Tạo connection cũ để tương thích với code cũ
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
  die('Kết nối CSDL thất bại: ' . $mysqli->connect_error);
}
// Set character set to utf8mb4 to support Vietnamese characters
$mysqli->set_charset('utf8mb4');
?>
