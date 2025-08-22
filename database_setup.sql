-- Tạo database
CREATE DATABASE IF NOT EXISTS taylaibui_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taylaibui_db;

-- Tạo bảng users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng vehicles
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('active', 'inactive', 'running', 'waiting', 'expired', 'paused') DEFAULT 'active',
    notes TEXT,
    route_number INT DEFAULT 0,
    group_timer INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tạo bảng logs
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Thêm dữ liệu mẫu cho users
INSERT INTO users (username, password, user_name, is_admin) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 1),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User Test', 0);

-- Thêm dữ liệu mẫu cho vehicles
INSERT INTO vehicles (plate_number, status, notes) VALUES 
('30A-12345', 'active', 'Xe hoạt động bình thường'),
('30A-67890', 'inactive', 'Xe trong xưởng sửa chữa'),
('30A-11111', 'running', 'Xe đang chạy tuyến 1'),
('30A-22222', 'waiting', 'Xe đang chờ khách');
