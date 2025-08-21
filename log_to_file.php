<?php
$xe = $_POST['xe'] ?? null;
$bat_dau = $_POST['bat_dau'] ?? null;
$ket_thuc = $_POST['ket_thuc'] ?? null;
$thoi_gian = $_POST['thoi_gian'] ?? null;

if (!$xe || !$bat_dau || !$ket_thuc || !$thoi_gian) {
  http_response_code(400);
  echo "Thiếu dữ liệu.";
  exit;
}

$logEntry = [
  'xe' => (int)$xe,
  'bat_dau' => $bat_dau,
  'ket_thuc' => $ket_thuc,
  'thoi_gian' => (int)$thoi_gian,
  'created_at' => date('Y-m-d H:i:s')
];

// Tạo thư mục logs nếu chưa có
$dir = __DIR__ . '/logs';
if (!is_dir($dir)) {
  mkdir($dir, 0755, true);
}

// Ghi vào file theo ngày
$filename = $dir . '/' . date('Y-m-d') . '.json';
$data = [];

if (file_exists($filename)) {
  $content = file_get_contents($filename);
  $data = json_decode($content, true) ?? [];
}

$data[] = $logEntry;
file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

echo "Ghi log thành công";
?>
