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
$repair_type = trim($_POST['repair_type'] ?? '');
$description = trim($_POST['description'] ?? '');
$cost = floatval($_POST['cost'] ?? 0);
$repair_date = $_POST['repair_date'] ?? '';
$technician = trim($_POST['technician'] ?? '');
$status = $_POST['status'] ?? 'pending';

if ($vehicle_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID xe không hợp lệ']);
    exit;
}

if (empty($repair_type)) {
    echo json_encode(['success' => false, 'message' => 'Loại sửa chữa không được để trống']);
    exit;
}

if (empty($repair_date)) {
    echo json_encode(['success' => false, 'message' => 'Ngày sửa chữa không được để trống']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

try {
    $mysqli->begin_transaction();
    
    // Thêm sửa chữa mới vào bảng repair_history
    $insert_query = "
        INSERT INTO repair_history (
            vehicle_id, repair_type, description, cost, repair_date, 
            technician, status, user_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $stmt = $mysqli->prepare($insert_query);
    $stmt->bind_param('issdsssi', $vehicle_id, $repair_type, $description, $cost, $repair_date, $technician, $status, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể thêm sửa chữa mới');
    }
    
    $new_repair_id = $mysqli->insert_id;
    
    // Cập nhật last_repair_id trong bảng vehicles
    $update_query = "
        UPDATE vehicles 
        SET last_repair_id = ?
        WHERE id = ?
    ";
    
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param('ii', $new_repair_id, $vehicle_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể cập nhật xe');
    }
    
    // Nếu trạng thái là "in_progress", cập nhật xe thành "Trong xưởng"
    if ($status === 'in_progress') {
        $update_vehicle_query = "
            UPDATE vehicles 
            SET active = 0 
            WHERE id = ?
        ";
        
        $stmt = $mysqli->prepare($update_vehicle_query);
        $stmt->bind_param('i', $vehicle_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật trạng thái xe');
        }
    }
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm sửa chữa mới thành công',
        'repair_id' => $new_repair_id
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
