<?php
// Test file để debug environment detection và database connection

echo "<h1>🔍 Environment Detection Test</h1>";

// Include config.php
require_once 'config.php';

echo "<h2>📊 Environment Information:</h2>";
echo "<p><strong>Current Environment:</strong> " . CURRENT_ENVIRONMENT . "</p>";
echo "<p><strong>Is Production:</strong> " . (IS_PRODUCTION ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Is Development:</strong> " . (IS_PRODUCTION ? 'No' : 'Yes') . "</p>";

echo "<h2>🌐 Server Information:</h2>";
echo "<p><strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "</p>";
echo "<p><strong>SERVER_ADDR:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'Not set') . "</p>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";

echo "<h2>📁 File Detection:</h2>";
echo "<p><strong>.env.production exists:</strong> " . (file_exists(__DIR__ . '/.env.production') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>env.local exists:</strong> " . (file_exists(__DIR__ . '/env.local') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>db.php exists:</strong> " . (file_exists(__DIR__ . '/db.php') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>db_production.php exists:</strong> " . (file_exists(__DIR__ . '/db_production.php') ? 'Yes' : 'No') . "</p>";

echo "<h2>🔧 Database Connection:</h2>";
if (isset($mysqli)) {
    echo "<p><strong>Database Connection:</strong> Connected</p>";
    echo "<p><strong>Database Host:</strong> " . $mysqli->host_info . "</p>";
    echo "<p><strong>Database Name:</strong> " . $mysqli->database . "</p>";
    
    // Test query
    $result = $mysqli->query("SELECT 1 as test");
    if ($result) {
        echo "<p><strong>Test Query:</strong> Success</p>";
    } else {
        echo "<p><strong>Test Query:</strong> Failed - " . $mysqli->error . "</p>";
    }
} else {
    echo "<p><strong>Database Connection:</strong> Not connected</p>";
}

echo "<h2>📝 Environment Variables:</h2>";
echo "<p><strong>APP_ENV:</strong> " . (getenv('APP_ENV') ?: 'Not set') . "</p>";
echo "<p><strong>ENVIRONMENT:</strong> " . (getenv('ENVIRONMENT') ?: 'Not set') . "</p>";

echo "<h2>🔍 Debug Logs:</h2>";
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    echo "<p><strong>Debug Mode:</strong> Enabled</p>";
    // Check error log
    $log_file = __DIR__ . '/logs/error.log';
    if (file_exists($log_file)) {
        echo "<p><strong>Error Log:</strong> " . $log_file . "</p>";
        $log_content = file_get_contents($log_file);
        if ($log_content) {
            echo "<pre>" . htmlspecialchars($log_content) . "</pre>";
        } else {
            echo "<p>Error log is empty</p>";
        }
    } else {
        echo "<p><strong>Error Log:</strong> File not found</p>";
    }
} else {
    echo "<p><strong>Debug Mode:</strong> Disabled</p>";
}

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
