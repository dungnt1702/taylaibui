<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("Kết nối database thất bại: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8");
    
    // Lấy danh sách tất cả người dùng
    $query = "SELECT id, name, phone, is_admin, is_active FROM users ORDER BY id";
    $result = $mysqli->query($query);
    
    if (!$result) {
        throw new Exception("Lỗi truy vấn: " . $mysqli->error);
    }
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'is_admin' => (bool)$row['is_admin'],
            'is_active' => (bool)$row['is_active']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>
