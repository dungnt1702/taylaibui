<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$vehicle_id = intval($_GET['vehicle_id'] ?? 0);
if ($vehicle_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID xe không hợp lệ']);
    exit;
}

try {
    // Lấy lịch sử sửa chữa của xe
    $query = "
        SELECT 
            rh.id,
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
        WHERE rh.vehicle_id = ?
        ORDER BY rh.repair_date DESC, rh.created_at DESC
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    if (empty($history)) {
        $html = '<p class="no-history">Chưa có lịch sử sửa chữa cho xe này.</p>';
    } else {
        $html = '<table class="history-table">';
        $html .= '<thead><tr><th>Ngày sửa</th><th>Loại sửa chữa</th><th>Mô tả</th><th>Chi phí</th><th>Thợ sửa</th><th>Trạng thái</th><th>Ngày hoàn thành</th><th>Người tạo</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($history as $record) {
            $statusClass = 'status-' . $record['status'];
            $statusText = [
                'pending' => 'Chờ xử lý',
                'in_progress' => 'Đang sửa',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy'
            ][$record['status']] ?? $record['status'];
            
            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y', strtotime($record['repair_date'])) . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($record['repair_type']) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($record['description']) . '</td>';
            $html .= '<td>' . ($record['cost'] > 0 ? number_format($record['cost'], 0, ',', '.') . ' VNĐ' : '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($record['technician'] ?? '-') . '</td>';
            $html .= '<td><span class="status-badge ' . $statusClass . '">' . $statusText . '</span></td>';
            $html .= '<td>' . ($record['completed_date'] ? date('d/m/Y', strtotime($record['completed_date'])) : '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($record['created_by'] ?? 'N/A') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
    }
    
    echo json_encode([
        'success' => true,
        'repairs' => $history,
        'count' => count($history)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
