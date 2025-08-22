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

$repair_id = intval($_POST['repair_id'] ?? 0);
$vehicle_id = intval($_POST['vehicle_id'] ?? 0);
$repair_type = trim($_POST['repair_type'] ?? '');
$description = trim($_POST['description'] ?? '');
$cost = floatval($_POST['cost'] ?? 0);
$repair_date = $_POST['repair_date'] ?? '';
$technician = trim($_POST['technician'] ?? '');
$status = $_POST['status'] ?? 'pending';

// Debug logging
error_log("POST data: " . print_r($_POST, true));
error_log("Repair ID: $repair_id, Vehicle ID: $vehicle_id");

if ($repair_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sửa chữa không hợp lệ']);
    exit;
}

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
    
    // Cập nhật sửa chữa trong bảng repair_history
    $update_query = "
        UPDATE repair_history 
        SET 
            vehicle_id = ?,
            repair_type = ?,
            description = ?,
            cost = ?,
            repair_date = ?,
            technician = ?,
            status = ?,
            updated_at = NOW()
        WHERE id = ?
    ";
    
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param('issdsssi', $vehicle_id, $repair_type, $description, $cost, $repair_date, $technician, $status, $repair_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể cập nhật sửa chữa');
    }
    
    // Cập nhật last_repair_id trong bảng vehicles nếu cần
    $check_last_query = "
        SELECT last_repair_id FROM vehicles WHERE id = ?
    ";
    $stmt = $mysqli->prepare($check_last_query);
    $stmt->bind_param('i', $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();
    
    if ($vehicle && $vehicle['last_repair_id'] == $repair_id) {
        // Nếu đây là sửa chữa cuối cùng của xe, cập nhật last_repair_id
        $update_vehicle_query = "
            UPDATE vehicles 
            SET last_repair_id = ?
            WHERE id = ?
        ";
        
        $stmt = $mysqli->prepare($update_vehicle_query);
        $stmt->bind_param('ii', $repair_id, $vehicle_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật xe');
        }
    }
    
    // Nếu trạng thái là "in_progress", cập nhật xe thành "Trong xưởng"
    if ($status === 'in_progress') {
        $update_vehicle_status_query = "
            UPDATE vehicles 
            SET active = 0 
            WHERE id = ?
        ";
        
        $stmt = $mysqli->prepare($update_vehicle_status_query);
        $stmt->bind_param('i', $vehicle_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể cập nhật trạng thái xe');
        }
    }
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật sửa chữa thành công'
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
