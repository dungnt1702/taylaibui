<?php
session_start();
// Deny access if user is not logged in (require either user_id or user_name)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name'])) {
  header('HTTP/1.1 403 Forbidden');
  echo 'Forbidden';
  exit;
}
$date = date('Y-m-d');
$file = __DIR__ . "/logs/$date.json";

if (!file_exists($file)) {
  echo "Không có log để xóa.";
  exit;
}

$id = $_POST['xe'];
$logs = json_decode(file_get_contents($file), true);
$logs = array_filter($logs, function($log) use ($id) {
  return $log['xe'] != $id;
});
file_put_contents($file, json_encode(array_values($logs), JSON_PRETTY_PRINT));
echo "Đã xóa log xe $id.";
?>