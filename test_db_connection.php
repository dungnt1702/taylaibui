<?php
echo "<h1>🔍 Test Database Connection</h1>";

// Test 1: Direct database connection
echo "<h2>Test 1: Direct Database Connection</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '', 'tay99672_qlss');
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Direct connection failed: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Direct connection successful</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
        
        // Test query
        $result = $mysqli->query("SELECT 1 as test");
        if ($result) {
            echo "<p style='color: green;'>✅ Query successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Query failed: " . $mysqli->error . "</p>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Include db.php
echo "<h2>Test 2: Include db.php</h2>";
try {
    require_once 'db.php';
    if (isset($mysqli)) {
        echo "<p style='color: green;'>✅ db.php loaded successfully</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
        
        // Test query
        $result = $mysqli->query("SELECT 1 as test");
        if ($result) {
            echo "<p style='color: green;'>✅ Query successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Query failed: " . $mysqli->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ \$mysqli not set after db.php</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Include config.php
echo "<h2>Test 3: Include config.php</h2>";
try {
    require_once 'config.php';
    echo "<p style='color: green;'>✅ config.php loaded successfully</p>";
    
    if (isset($mysqli)) {
        echo "<p style='color: green;'>✅ \$mysqli is set after config.php</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
        
        // Test query
        $result = $mysqli->query("SELECT 1 as test");
        if ($result) {
            echo "<p style='color: green;'>✅ Query successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Query failed: " . $mysqli->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ \$mysqli not set after config.php</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
