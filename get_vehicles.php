<?php
require_once 'config.php'; // Sử dụng config.php để tự động detect environment
session_start();
// Deny access if user is not logged in (require either user_id or user_name)
if (!isset($_SESSION['user_id']) && !isset($_COOKIE['remember_login'])) {
  header('HTTP/1.1 403 Forbidden');
  echo json_encode([]);
  exit;
}

$result = $mysqli->query("
  SELECT 
    v.*,
    COALESCE(mh.status, '') as maintenance_status,
    COALESCE(mh.notes, '') as maintenance_notes,
    COALESCE(rh.status, '') as last_repair_status
  FROM vehicles v
  LEFT JOIN maintenance_history mh ON v.last_maintenance_id = mh.id
  LEFT JOIN repair_history rh ON v.last_repair_id = rh.id
  ORDER BY v.id
");
$data = [];

while ($row = $result->fetch_assoc()) {
  $row['active'] = (bool)$row['active'];
  $row['paused'] = (bool)$row['paused'];
  $row['notifiedEnd'] = (bool)$row['notifiedEnd'];
    // Chuyển đổi giá trị cung đường: 0 nghĩa là null
    if (isset($row['routeNumber'])) {
      $row['routeNumber'] = $row['routeNumber'] ? (int)$row['routeNumber'] : null;
    }
    if (isset($row['routeStartAt'])) {
      $row['routeStartAt'] = $row['routeStartAt'] ? (int)$row['routeStartAt'] : null;
    }
  $data[$row['id']] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

$mysqli->close();
?>
