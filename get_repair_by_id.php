<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$repair_id = intval($_GET['id'] ?? 0);
if ($repair_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sửa chữa không hợp lệ']);
    exit;
}

try {
    // Lấy thông tin sửa chữa theo ID
    $query = "
        SELECT 
            id,
            vehicle_id,
            repair_type,
            description,
            cost,
            repair_date,
            completed_date,
            status,
            technician,
            created_at
        FROM repair_history 
        WHERE id = ?
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $repair_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sửa chữa với ID này'
        ]);
        exit;
    }
    
    $repair = $result->fetch_assoc();
    
    // Format dates cho input fields
    $repair['repair_date'] = date('Y-m-d', strtotime($repair['repair_date']));
    if ($repair['completed_date']) {
        $repair['completed_date'] = date('Y-m-d', strtotime($repair['completed_date']));
    }
    
    echo json_encode([
        'success' => true,
        'repair' => $repair
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
