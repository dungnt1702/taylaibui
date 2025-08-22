<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

// Debug logging
error_log("update_user.php called");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra quyền: admin hoặc user cập nhật chính mình
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$currentUserId = $_SESSION['user_id'];
$requestedUserId = (int)($_POST['user_id'] ?? 0);

// Nếu không phải admin, chỉ cho phép cập nhật thông tin của chính mình
if (!$isAdmin && $requestedUserId !== $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể cập nhật thông tin của chính mình']);
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ method POST']);
    exit;
}

// Lấy dữ liệu từ form
$userId = (int)($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = (int)($_POST['role'] ?? 0);

// Validate dữ liệu
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID người dùng không hợp lệ']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Tên người dùng không được để trống']);
    exit;
}

// Nếu không phải admin, không cho phép thay đổi role
if (!$isAdmin) {
    $role = null; // Không cập nhật role
}

// Nếu không phải admin, không cho phép thay đổi phone
if (!$isAdmin) {
    $phone = null; // Không cập nhật phone
}

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("Kết nối database thất bại: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8");
    
    // Kiểm tra người dùng có tồn tại không
    $checkQuery = "SELECT id FROM users WHERE id = ?";
    $checkStmt = $mysqli->prepare($checkQuery);
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
        $checkStmt->close();
        $mysqli->close();
        exit;
    }
    $checkStmt->close();
    
    // Kiểm tra số điện thoại đã tồn tại ở người dùng khác chưa (chỉ cho admin)
    if ($isAdmin && !empty($phone)) {
        $phoneCheckQuery = "SELECT id FROM users WHERE phone = ? AND id != ?";
        $phoneCheckStmt = $mysqli->prepare($phoneCheckQuery);
        $phoneCheckStmt->bind_param("si", $phone, $userId);
        $phoneCheckStmt->execute();
        $phoneCheckResult = $phoneCheckStmt->get_result();
        
        if ($phoneCheckResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại ở người dùng khác']);
            $phoneCheckStmt->close();
            $mysqli->close();
            exit;
        }
        $phoneCheckStmt->close();
    }
    
    // Cập nhật thông tin người dùng
    if ($isAdmin) {
        // Admin có thể cập nhật tất cả thông tin
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET name = ?, phone = ?, password = ?, is_admin = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);
            $updateStmt->bind_param("sssii", $name, $phone, $hashedPassword, $role, $userId);
        } else {
            $updateQuery = "UPDATE users SET name = ?, phone = ?, is_admin = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);
            $updateStmt->bind_param("ssii", $name, $phone, $role, $userId);
        }
    } else {
        // User thường chỉ có thể cập nhật tên và password
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET name = ?, password = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);
            $updateStmt->bind_param("ssi", $name, $hashedPassword, $userId);
        } else {
            $updateQuery = "UPDATE users SET name = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);
            $updateStmt->bind_param("si", $name, $userId);
        }
    }
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật thông tin người dùng thành công'
        ]);
    } else {
        throw new Exception("Lỗi khi cập nhật người dùng: " . $updateStmt->error);
    }
    
    $updateStmt->close();
    
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
