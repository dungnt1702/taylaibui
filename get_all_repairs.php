<?php
require_once 'config.php';
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    // Lấy tất cả dữ liệu sửa chữa
    $query = "
        SELECT 
            rh.id,
            rh.vehicle_id,
            rh.repair_type,
            rh.description,
            rh.cost,
            rh.repair_date,
            rh.completed_date,
            rh.status,
            rh.technician,
            rh.created_at,
            u.name as created_by
        FROM repair_history rh
        LEFT JOIN users u ON rh.user_id = u.id
        ORDER BY rh.created_at DESC
    ";
    
    $result = $mysqli->query($query);
    
    if (!$result) {
        throw new Exception('Không thể truy vấn dữ liệu sửa chữa');
    }
    
    $repairs = [];
    while ($row = $result->fetch_assoc()) {
        // Format dates
        $row['repair_date'] = date('d/m/Y', strtotime($row['repair_date']));
        if ($row['completed_date']) {
            $row['completed_date'] = date('d/m/Y', strtotime($row['completed_date']));
        }
        $row['created_at'] = date('d/m/Y H:i', strtotime($row['created_at']));
        
        // Format cost
        $row['cost'] = floatval($row['cost']);
        
        $repairs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'repairs' => $repairs,
        'total' => count($repairs)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
