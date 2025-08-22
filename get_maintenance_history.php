<?php
require_once 'config.php';
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
    // Lấy lịch sử bảo dưỡng của xe
    $query = "
        SELECT 
            mh.id,
            mh.status,
            mh.notes,
            mh.created_at,
            u.name as updated_by
        FROM maintenance_history mh
        LEFT JOIN users u ON mh.user_id = u.id
        WHERE mh.vehicle_id = ?
        ORDER BY mh.created_at DESC
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
        $html = '<p class="no-history">Chưa có lịch sử bảo dưỡng cho xe này.</p>';
    } else {
        $html = '<table class="history-table">';
        $html .= '<thead><tr><th>Thời gian</th><th>Tình trạng</th><th>Ghi chú</th><th>Người cập nhật</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($history as $record) {
            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y H:i', strtotime($record['created_at'])) . '</td>';
            $html .= '<td><span class="status-badge">' . htmlspecialchars($record['status']) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($record['notes']) . '</td>';
            $html .= '<td>' . htmlspecialchars($record['updated_by'] ?? 'N/A') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($history)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
