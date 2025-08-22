<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
    exit;
}

$vehicle_id = intval($_POST['vehicle_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($vehicle_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID xe không hợp lệ']);
    exit;
}

if (empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Tình trạng không được để trống']);
    exit;
}

try {
    $mysqli->begin_transaction();
    
    // Thêm tình trạng mới vào bảng maintenance_history
    $insert_query = "
        INSERT INTO maintenance_history (vehicle_id, status, notes, user_id, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ";
    
    $stmt = $mysqli->prepare($insert_query);
    $stmt->bind_param('issi', $vehicle_id, $status, $notes, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể thêm tình trạng mới');
    }
    
    $new_maintenance_id = $mysqli->insert_id;
    
    // Cập nhật last_maintenance_id trong bảng vehicles
    $update_query = "
        UPDATE vehicles 
        SET last_maintenance_id = ?
        WHERE id = ?
    ";
    
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param('ii', $new_maintenance_id, $vehicle_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể cập nhật xe');
    }
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm tình trạng mới thành công',
        'maintenance_id' => $new_maintenance_id
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
