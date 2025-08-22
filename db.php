<?php
// Cấu hình database cho localhost
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tay99672_qlss');

// Hàm tạo database và bảng
function setupDatabase() {
    global $mysqli;
    
    // Kết nối MySQL không chỉ định database
    $temp_mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($temp_mysqli->connect_error) {
        die('Kết nối MySQL thất bại: ' . $temp_mysqli->connect_error);
    }
    
    // Tạo database nếu chưa có
    if (!$temp_mysqli->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME)) {
        die('Không thể tạo database: ' . $temp_mysqli->error);
    }
    
    $temp_mysqli->close();
    
    // Kết nối đến database đã tạo
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        die('Kết nối CSDL thất bại: ' . $mysqli->connect_error);
    }
    
    // Set character set
    $mysqli->set_charset('utf8mb4');
    
    // Định nghĩa các bảng
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL UNIQUE,
            name VARCHAR(100),
            password VARCHAR(255),
            is_admin TINYINT DEFAULT 0,
            is_active TINYINT DEFAULT 1
        )",
        'vehicles' => "CREATE TABLE IF NOT EXISTS vehicles (
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
        'log' => "CREATE TABLE IF NOT EXISTS log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            xe INT,
            bat_dau DATETIME,
            ket_thuc DATETIME,
            thoi_gian INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'repair_history' => "CREATE TABLE IF NOT EXISTS repair_history (
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
        'maintenance_history' => "CREATE TABLE IF NOT EXISTS maintenance_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT,
            status VARCHAR(100),
            notes TEXT,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    // Tạo các bảng
    foreach ($tables as $table_name => $create_sql) {
        if (!$mysqli->query($create_sql)) {
            echo '<p>Lỗi tạo bảng ' . $table_name . ': ' . $mysqli->error . '</p>';
        }
    }
    
    echo '<p>✅ Database setup completed!</p>';
}

// Thực hiện setup database
setupDatabase();

// Đảm bảo $mysqli luôn tồn tại
if (!isset($mysqli) || $mysqli->connect_error) {
    die('Database setup failed! $mysqli is not available.');
}
?>
