<?php
// Cấu hình database cho localhost
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tay99672_qlss');

// Tạo connection cũ để tương thích với code cũ
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
  // Nếu database không tồn tại, thử tạo database
  if ($mysqli->errno == 1049) { // Unknown database
    echo '<p>Database ' . DB_NAME . ' không tồn tại. Đang tạo database...</p>';
    
    // Kết nối MySQL không chỉ định database
    $temp_mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($temp_mysqli->connect_error) {
      die('Kết nối MySQL thất bại: ' . $temp_mysqli->connect_error);
    }
    
    // Tạo database
    if ($temp_mysqli->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME)) {
      echo '<p>Database ' . DB_NAME . ' đã được tạo thành công!</p>';
      $temp_mysqli->close();
      
      // Thử kết nối lại
      $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
      if ($mysqli->connect_error) {
        die('Kết nối CSDL thất bại sau khi tạo: ' . $mysqli->connect_error);
      }
    } else {
      die('Không thể tạo database: ' . $temp_mysqli->error);
    }
  } else {
    die('Kết nối CSDL thất bại: ' . $mysqli->connect_error);
  }
}

// Set character set to utf8mb4 to support Vietnamese characters
$mysqli->set_charset('utf8mb4');

// Kiểm tra và tạo bảng cần thiết nếu chưa có
$tables = [
    'users' => "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100),
        password VARCHAR(255),
        is_admin TINYINT DEFAULT 0,
        is_active TINYINT DEFAULT 1
    )",
    'vehicles' => "CREATE TABLE vehicles (
        id INT PRIMARY KEY,
        active TINYINT DEFAULT 1,
        endAt BIGINT,
        paused TINYINT DEFAULT 0,
        remaining INT,
        minutes INT,
        notifiedEnd TINYINT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        routeNumber TINYINT UNSIGNED DEFAULT 0,
        routeStartAt BIGINT UNSIGNED DEFAULT 0,
        repairNotes TEXT
    )",
    'log' => "CREATE TABLE log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        xe INT,
        bat_dau DATETIME,
        ket_thuc DATETIME,
        thoi_gian INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'repair_history' => "CREATE TABLE repair_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT,
        repair_type VARCHAR(100),
        description TEXT,
        cost DECIMAL(10,2) DEFAULT 0,
        repair_date DATE,
        completed_date DATE NULL,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        technician VARCHAR(100),
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by VARCHAR(100) DEFAULT 'N/A'
    )",
    'maintenance_history' => "CREATE TABLE maintenance_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT,
        status VARCHAR(100),
        notes TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $table_name => $create_sql) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table_name'");
    if ($result->num_rows == 0) {
        echo '<p>Bảng ' . $table_name . ' không tồn tại. Đang tạo bảng...</p>';
        if ($mysqli->query($create_sql)) {
            echo '<p>Bảng ' . $table_name . ' đã được tạo thành công!</p>';
        } else {
            echo '<p>Lỗi tạo bảng ' . $table_name . ': ' . $mysqli->error . '</p>';
        }
    }
}

echo '<p>✅ Database setup completed!</p>';
?>
