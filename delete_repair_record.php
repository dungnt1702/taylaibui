<?php
require_once 'config.php';
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

$repair_id = intval($_POST['repair_id'] ?? 0);
if ($repair_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sửa chữa không hợp lệ']);
    exit;
}

try {
    $mysqli->begin_transaction();
    
    // Lấy thông tin sửa chữa trước khi xóa
    $get_repair_query = "SELECT vehicle_id, id FROM repair_history WHERE id = ?";
    $stmt = $mysqli->prepare($get_repair_query);
    $stmt->bind_param('i', $repair_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Không tìm thấy sửa chữa với ID này');
    }
    
    $repair = $result->fetch_assoc();
    $vehicle_id = $repair['vehicle_id'];
    
    // Xóa sửa chữa
    $delete_query = "DELETE FROM repair_history WHERE id = ?";
    $stmt = $mysqli->prepare($delete_query);
    $stmt->bind_param('i', $repair_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể xóa sửa chữa');
    }
    
    // Kiểm tra xem có phải là sửa chữa cuối cùng của xe không
    $check_last_query = "SELECT last_repair_id FROM vehicles WHERE id = ?";
    $stmt = $mysqli->prepare($check_last_query);
    $stmt->bind_param('i', $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();
    
    if ($vehicle && $vehicle['last_repair_id'] == $repair_id) {
        // Nếu đây là sửa chữa cuối cùng, cập nhật last_repair_id
        $update_vehicle_query = "
            UPDATE vehicles 
            SET last_repair_id = (
                SELECT id FROM repair_history 
                WHERE vehicle_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            )
            WHERE id = ?
        ";
        
        $stmt = $mysqli->prepare($update_vehicle_query);
        $stmt->bind_param('ii', $vehicle_id, $vehicle_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật xe');
        }
    }
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sửa chữa thành công'
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
