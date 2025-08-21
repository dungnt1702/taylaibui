<?php
session_start();
// Deny access if user is not logged in (require either user_id or user_name)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name'])) {
  header('HTTP/1.1 403 Forbidden');
  header('Content-Type: application/json');
  echo json_encode([]);
  exit;
}
$date = date('Y-m-d');
$file = __DIR__ . "/logs/$date.json";

header('Content-Type: application/json');
if (file_exists($file)) {
  echo file_get_contents($file);
} else {
  echo json_encode([]);
}
?>