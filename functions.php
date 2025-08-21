<?php
function get_vehicles($db) {
  $res = $db->query("SELECT * FROM vehicles ORDER BY id ASC");
  $result = [];
  while ($row = $res->fetch_assoc()) {
    $result[(int)$row['id']] = $row;
  }
  return $result;
}

function match_filter($v, $filter) {
  $now = time() * 1000;
  $endAt = isset($v['endAt']) ? (int)$v['endAt'] : null;
  $paused = !empty($v['paused']);
  $active = !empty($v['active']);
  $seconds_left = $endAt ? floor(($endAt - $now) / 1000) : 0;

  switch ($filter) {
    case 'active': return $active;
    case 'inactive': return !$active;
    case 'running': return $active && $endAt && !$paused && $seconds_left > 0;
    case 'paused': return $paused && $endAt && $endAt > $now;
    case 'expired': return $endAt && $seconds_left <= 0;
    case 'waiting': return $active && !$paused && (!$endAt || $v['endAt'] == null);
    default: return true;
  }
}

function get_border_class($v) {
  $now = time() * 1000;
  $endAt = isset($v['endAt']) ? (int)$v['endAt'] : null;
  $paused = !empty($v['paused']);
  $active = !empty($v['active']);
  $seconds_left = $endAt ? floor(($endAt - $now) / 1000) : 0;

  if (!$active) return 'inactive';
  if ($endAt && $seconds_left <= 0) return 'expired';
  if ($endAt && $seconds_left <= 60) return 'warning';
  if ($endAt && !$paused) return 'running';
  return '';
}

function get_timer_display($v) {
  $endAt = isset($v['endAt']) ? (int)$v['endAt'] : null;
  $paused = !empty($v['paused']);
  $now = time() * 1000;

  if ($paused) return 'Tạm hoãn';
  if ($endAt && $endAt < $now) return 'Hết giờ';
  if ($endAt) {
    $sec = floor(($endAt - $now) / 1000);
    $m = str_pad(floor($sec / 60), 2, '0', STR_PAD_LEFT);
    $s = str_pad($sec % 60, 2, '0', STR_PAD_LEFT);
    return "$m:$s";
  }
  return "00:00";
}

function render_controls($id, $v) {
  $active = !empty($v['active']);
  $paused = !empty($v['paused']);
  $endAt = isset($v['endAt']) ? (int)$v['endAt'] : null;
  $now = time() * 1000;

  if (!$active) {
    echo "<button onclick='startVehicle($id)' style='background:#28a745;color:white;'>🟢 Bật xe</button>";
    return;
  }
  if (!$endAt) {
    echo "<button onclick='startTimer($id,15)'>Bắt đầu 15p</button>
          <button onclick='startTimer($id,30)'>Bắt đầu 30p</button>";
  }
  if ($endAt && !$paused && $endAt > $now) {
    echo "<button onclick='addTime($id,15)'>+15p</button>
          <button onclick='pauseTimer($id)'>Tạm hoãn</button>";
  }
  if ($paused && $endAt) {
    echo "<button onclick='resumeTimer($id)'>Tiếp tục</button>";
  }
  echo "<button onclick='resetTimer($id)'>Reset</button>
        <button onclick='stopVehicle($id)' style='background:#6c757d;color:white;'>⚪ Tắt xe</button>";
}
