<?php
echo "<h1>🔍 Simple Database Test</h1>";

// Test 1: Direct include db.php
echo "<h2>Test 1: Direct include db.php</h2>";
try {
    require_once 'db.php';
    if (isset($mysqli)) {
        echo "<p style='color: green;'>✅ db.php loaded successfully</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
        
        // Test query
        $result = $mysqli->query("SELECT 1 as test");
        if ($result) {
            echo "<p style='color: green;'>✅ Database query successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Database query failed: " . $mysqli->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ \$mysqli not set</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Test config.php
echo "<h2>Test 2: Test config.php</h2>";
try {
    require_once 'config.php';
    echo "<p>Environment: " . (defined('CURRENT_ENVIRONMENT') ? CURRENT_ENVIRONMENT : 'Not defined') . "</p>";
    echo "<p>Is Production: " . (defined('IS_PRODUCTION') ? (IS_PRODUCTION ? 'Yes' : 'No') : 'Not defined') . "</p>";
    echo "<p>Is Development: " . (defined('IS_DEVELOPMENT') ? (IS_DEVELOPMENT ? 'Yes' : 'No') : 'Not defined') . "</p>";
    
    if (isset($mysqli)) {
        echo "<p style='color: green;'>✅ Database connected via config.php</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Database not connected via config.php</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
