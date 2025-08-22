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
$userId = (int)($_POST['user_id'] ?? 0);
$newStatus = (int)($_POST['status'] ?? 0);

// Validate dữ liệu
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID người dùng không hợp lệ']);
    exit;
}

if ($newStatus !== 0 && $newStatus !== 1) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

// Không cho phép tắt chính mình
if ($userId == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Không thể tắt tài khoản của chính mình']);
    exit;
}

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("Kết nối database thất bại: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8");
    
    // Kiểm tra người dùng có tồn tại không
    $checkQuery = "SELECT id, is_active FROM users WHERE id = ?";
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
    
    $userData = $checkResult->fetch_assoc();
    $currentStatus = $userData['is_active'];
    $checkStmt->close();
    
    // Nếu trạng thái không thay đổi
    if ($currentStatus == $newStatus) {
        $statusText = $newStatus ? 'đang hoạt động' : 'đang bị tắt';
        echo json_encode(['success' => false, 'message' => "Người dùng $statusText rồi"]);
        $mysqli->close();
        exit;
    }
    
    // Cập nhật trạng thái người dùng
    $updateQuery = "UPDATE users SET is_active = ? WHERE id = ?";
    $updateStmt = $mysqli->prepare($updateQuery);
    $updateStmt->bind_param("ii", $newStatus, $userId);
    
    if ($updateStmt->execute()) {
        $statusText = $newStatus ? 'đã bật' : 'đã tắt';
        echo json_encode([
            'success' => true,
            'message' => "Đã $statusText người dùng thành công",
            'new_status' => $newStatus
        ]);
    } else {
        throw new Exception("Lỗi khi cập nhật trạng thái người dùng: " . $updateStmt->error);
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
