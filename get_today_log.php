<?php
$date = date('Y-m-d');
$file = __DIR__ . "/logs/$date.json";

header('Content-Type: application/json');
if (file_exists($file)) {
  echo file_get_contents($file);
} else {
  echo json_encode([]);
}
?>