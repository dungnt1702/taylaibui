<?php
require_once 'db.php';
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

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ method POST']);
    exit;
}

// Lấy dữ liệu từ form
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$role = (int)($_POST['role'] ?? 0);

// Validate dữ liệu
if (empty($name) || empty($phone) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

if ($role !== 0 && $role !== 1) {
    echo json_encode(['success' => false, 'message' => 'Quyền không hợp lệ']);
    exit;
}

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("Kết nối database thất bại: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8");
    
    // Kiểm tra số điện thoại đã tồn tại chưa
    $checkQuery = "SELECT id FROM users WHERE phone = ?";
    $checkStmt = $mysqli->prepare($checkQuery);
    $checkStmt->bind_param("s", $phone);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại']);
        $checkStmt->close();
        $mysqli->close();
        exit;
    }
    $checkStmt->close();
    
    // Hash mật khẩu
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Thêm người dùng mới
    $insertQuery = "INSERT INTO users (name, phone, password, is_admin, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())";
    $insertStmt = $mysqli->prepare($insertQuery);
    $insertStmt->bind_param("sssi", $name, $phone, $hashedPassword, $role);
    
    if ($insertStmt->execute()) {
        $newUserId = $mysqli->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm người dùng mới thành công',
            'user_id' => $newUserId
        ]);
    } else {
        throw new Exception("Lỗi khi thêm người dùng: " . $insertStmt->error);
    }
    
    $insertStmt->close();
    
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
