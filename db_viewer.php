<?php
// Kết nối database
$mysqli = new mysqli('localhost', 'root', '', 'tay99672_qlss');

if ($mysqli->connect_error) {
    die('Kết nối CSDL thất bại: ' . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");

// Lấy danh sách bảng
$tables = [];
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

// Lấy dữ liệu từ bảng được chọn
$selectedTable = $_GET['table'] ?? $tables[0] ?? '';
$tableData = [];
$columns = [];

if ($selectedTable) {
    $result = $mysqli->query("SELECT * FROM `$selectedTable` LIMIT 100");
    if ($result) {
        $columns = [];
        while ($field = $result->fetch_field()) {
            $columns[] = $field->name;
        }
        
        while ($row = $result->fetch_assoc()) {
            $tableData[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Viewer - TAY LÁI BỤI SÓC SƠN</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #f57c00; text-align: center; margin-bottom: 30px; }
        .table-selector { margin-bottom: 20px; }
        select { padding: 10px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px; width: 200px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; color: #333; }
        tr:hover { background: #f9f9f9; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .status-active { background: #e8f5e8; color: #2e7d32; }
        .status-inactive { background: #ffebee; color: #c62828; }
        .btn { padding: 8px 16px; background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Viewer - TAY LÁI BỤI SÓC SƠN</h1>
        
        <div class="table-selector">
            <label for="table">Chọn bảng:</label>
            <select id="table" onchange="changeTable(this.value)">
                <?php foreach ($tables as $table): ?>
                    <option value="<?= htmlspecialchars($table) ?>" <?= $table === $selectedTable ? 'selected' : '' ?>>
                        <?= htmlspecialchars($table) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($selectedTable && !empty($columns)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th><?= htmlspecialchars($column) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableData as $row): ?>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <td>
                                        <?php 
                                        $value = $row[$column];
                                        if ($column === 'active') {
                                            echo '<span class="status ' . ($value ? 'status-active' : 'status-inactive') . '">' . 
                                                 ($value ? 'Hoạt động' : 'Không hoạt động') . '</span>';
                                        } elseif (in_array($column, ['endAt', 'routeStartAt', 'updated_at', 'created_at']) && $value) {
                                            // Handle timestamp columns (convert from milliseconds to seconds if needed)
                                            if (is_numeric($value)) {
                                                $timestamp = $value;
                                                if ($timestamp > 9999999999) { // If timestamp is in milliseconds
                                                    $timestamp = intval($timestamp / 1000);
                                                }
                                                echo date('d/m/Y H:i:s', $timestamp);
                                            } elseif (is_string($value) && strtotime($value)) {
                                                // If value is already a datetime string, format it
                                                echo date('d/m/Y H:i:s', strtotime($value));
                                            } else {
                                                // Fallback: display as is
                                                echo htmlspecialchars($value);
                                            }
                                        } elseif ($column === 'remaining' && $value) {
                                            // Display remaining time in minutes:seconds format
                                            $minutes = intval($value / 60);
                                            $seconds = $value % 60;
                                            echo sprintf('%02d:%02d', $minutes, $seconds);
                                        } else {
                                            echo htmlspecialchars($value ?? '');
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <button class="btn" onclick="location.reload()">🔄 Làm mới</button>
                <button class="btn" onclick="window.open('?filter=group', '_blank')">🚗 Xem trang Khách đoàn</button>
            </div>
        <?php else: ?>
            <p>Không có dữ liệu để hiển thị.</p>
        <?php endif; ?>
    </div>

    <script>
        function changeTable(tableName) {
            window.location.href = '?table=' + encodeURIComponent(tableName);
        }
    </script>
</body>
</html>
