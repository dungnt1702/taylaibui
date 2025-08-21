<?php
include 'db.php';
session_start();
// Deny access if user is not logged in (require either user_id or user_name)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name'])) {
  header('HTTP/1.1 403 Forbidden');
  echo 'Forbidden';
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)$data['id'];
$active = isset($data['active']) ? (int)$data['active'] : 0;
$endAt = isset($data['endAt']) ? (int)$data['endAt'] : null;
$paused = isset($data['paused']) ? (int)$data['paused'] : 0;
$remaining = isset($data['remaining']) ? (int)$data['remaining'] : null;
$minutes = isset($data['minutes']) ? (int)$data['minutes'] : null;
$notifiedEnd = isset($data['notifiedEnd']) ? (int)$data['notifiedEnd'] : 0;

// Thêm thông tin cung đường: routeNumber và routeStartAt. Sử dụng 0 để biểu thị null.
$routeNumber = isset($data['routeNumber']) ? (int)$data['routeNumber'] : 0;
$routeStartAt = isset($data['routeStartAt']) ? (int)$data['routeStartAt'] : 0;

// Lưu thêm trường repairNotes (text)
$repairNotes = isset($data['repairNotes']) ? $data['repairNotes'] : null;
$stmt = $mysqli->prepare("REPLACE INTO vehicles (id, active, endAt, paused, remaining, minutes, notifiedEnd, routeNumber, routeStartAt, repairNotes)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiiiiiis", $id, $active, $endAt, $paused, $remaining, $minutes, $notifiedEnd, $routeNumber, $routeStartAt, $repairNotes);

if ($stmt->execute()) {
  echo "OK";
} else {
  http_response_code(500);
  echo "Lỗi: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
