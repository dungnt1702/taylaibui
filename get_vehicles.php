<?php
include 'db.php'; // biến kết nối là $mysqli
session_start();
// Deny access if user is not logged in (require either user_id or user_name)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name'])) {
  header('HTTP/1.1 403 Forbidden');
  echo json_encode([]);
  exit;
}

$result = $mysqli->query("SELECT * FROM vehicles ORDER BY id");
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
