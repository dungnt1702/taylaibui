<?php
// Cấu hình database cho localhost
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tay99672_qlss');

// Kết nối trực tiếp đến database
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Kiểm tra kết nối
    if ($mysqli->connect_error) {
        error_log("Database connection failed: " . $mysqli->connect_error);
        die('Kết nối CSDL thất bại: ' . $mysqli->connect_error);
    }
    
    // Set character set
    if (!$mysqli->set_charset('utf8mb4')) {
        error_log("Failed to set charset: " . $mysqli->error);
    }
    
    // Log successful connection
    error_log("Database connection successful - Host: " . $mysqli->host_info);
    
} catch (Exception $e) {
    error_log("Exception creating mysqli: " . $e->getMessage());
    die('Lỗi tạo kết nối CSDL: ' . $e->getMessage());
}

// Verify $mysqli is set
if (!isset($mysqli)) {
    error_log("$mysqli variable is not set after creation");
    die('Biến $mysqli không được tạo');
}

if (!($mysqli instanceof mysqli)) {
    error_log("$mysqli is not a mysqli object");
    die('Biến $mysqli không phải là mysqli object');
}
?>
