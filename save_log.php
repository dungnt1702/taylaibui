<?php
session_start();
// Deny access if user is not logged in (require either user_id or user_name)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name'])) {
  http_response_code(403);
  echo 'Forbidden';
  exit;
}
$date = date('Y-m-d');
$folder = __DIR__ . '/logs';
if (!is_dir($folder)) mkdir($folder);
$file = "$folder/$date.json";

$data = [
  'xe' => $_POST['xe'],
  'bat_dau' => $_POST['bat_dau'],
  'ket_thuc' => $_POST['ket_thuc'],
  'thoi_gian' => $_POST['thoi_gian']
];

$logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
$logs[] = $data;
file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT));
echo "Ghi log thành công.";
?>
