<?php
echo "<h1>🔍 Debug Config.php</h1>";

// Test 1: Include config.php
echo "<h2>Test 1: Include config.php</h2>";
try {
    require_once 'config.php';
    echo "<p style='color: green;'>✅ config.php loaded successfully</p>";
    
    // Check if constants are defined
    echo "<p><strong>CURRENT_ENVIRONMENT:</strong> " . (defined('CURRENT_ENVIRONMENT') ? CURRENT_ENVIRONMENT : 'Not defined') . "</p>";
    echo "<p><strong>IS_PRODUCTION:</strong> " . (defined('IS_PRODUCTION') ? (IS_PRODUCTION ? 'Yes' : 'No') : 'Not defined') . "</p>";
    echo "<p><strong>IS_DEVELOPMENT:</strong> " . (defined('IS_DEVELOPMENT') ? (IS_DEVELOPMENT ? 'Yes' : 'No') : 'Not defined') . "</p>";
    echo "<p><strong>DEBUG_MODE:</strong> " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'Yes' : 'No') : 'Not defined') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading config.php: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
if (isset($mysqli)) {
    echo "<p style='color: green;'>✅ \$mysqli is set</p>";
    echo "<p><strong>Host Info:</strong> " . $mysqli->host_info . "</p>";
    echo "<p><strong>Database:</strong> " . $mysqli->database . "</p>";
    
    // Test query
    $result = $mysqli->query("SELECT 1 as test");
    if ($result) {
        echo "<p style='color: green;'>✅ Database query successful</p>";
        $row = $result->fetch_assoc();
        echo "<p><strong>Test Result:</strong> " . $row['test'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Database query failed: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ \$mysqli is NOT set</p>";
}

echo "<hr>";

// Test 3: Check environment detection
echo "<h2>Test 3: Environment Detection</h2>";
if (function_exists('detectEnvironment')) {
    $env = detectEnvironment();
    echo "<p><strong>Detected Environment:</strong> " . $env . "</p>";
} else {
    echo "<p style='color: red;'>❌ detectEnvironment function not found</p>";
}

echo "<hr>";

// Test 4: Check server variables
echo "<h2>Test 4: Server Variables</h2>";
echo "<p><strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "</p>";
echo "<p><strong>SERVER_ADDR:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'Not set') . "</p>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";

echo "<hr>";

// Test 5: Check file existence
echo "<h2>Test 5: File Existence</h2>";
echo "<p><strong>config.php:</strong> " . (file_exists('config.php') ? 'Exists' : 'Not found') . "</p>";
echo "<p><strong>db.php:</strong> " . (file_exists('db.php') ? 'Exists' : 'Not found') . "</p>";
echo "<p><strong>db_production.php:</strong> " . (file_exists('db_production.php') ? 'Exists' : 'Not found') . "</p>";
echo "<p><strong>env.local:</strong> " . (file_exists('env.local') ? 'Exists' : 'Not found') . "</p>";

echo "<hr>";
echo "<p><em>Debug completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
