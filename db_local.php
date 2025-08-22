<?php
// Cấu hình database cho localhost
$mysqli = new mysqli('localhost', 'root', '', 'tay99672_qlss');
if ($mysqli->connect_error) {
  die('Kết nối CSDL thất bại: ' . $mysqli->connect_error);
}
// Set character set to utf8mb4 to support Vietnamese characters
$mysqli->set_charset('utf8mb4');
?>
