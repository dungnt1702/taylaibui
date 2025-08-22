<?php
echo "<h1>🔍 Check Available Databases</h1>";

// Test 1: Connect without specifying database
echo "<h2>Test 1: Connect to MySQL without database</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '');
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Connection failed: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Connected to MySQL successfully</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        
        // Show all databases
        $result = $mysqli->query("SHOW DATABASES");
        if ($result) {
            echo "<h3>Available Databases:</h3>";
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . $row['Database'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ Failed to get databases: " . $mysqli->error . "</p>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Try to connect to specific database
echo "<h2>Test 2: Try to connect to tay99672_qlss</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '', 'tay99672_qlss');
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Connection to tay99672_qlss failed: " . $mysqli->connect_error . "</p>";
        
        // Check if database exists
        $temp_mysqli = new mysqli('localhost', 'root', '');
        if (!$temp_mysqli->connect_error) {
            $result = $temp_mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'tay99672_qlss'");
            if ($result && $result->num_rows > 0) {
                echo "<p style='color: green;'>✅ Database tay99672_qlss exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Database tay99672_qlss does NOT exist</p>";
            }
            $temp_mysqli->close();
        }
    } else {
        echo "<p style='color: green;'>✅ Connection to tay99672_qlss successful</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
        
        // Show tables
        $result = $mysqli->query("SHOW TABLES");
        if ($result) {
            echo "<h3>Tables in tay99672_qlss:</h3>";
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . $row['Tables_in_tay99672_qlss'] . "</li>";
            }
            echo "</ul>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Try to create database if it doesn't exist
echo "<h2>Test 3: Try to create database tay99672_qlss</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '');
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Connection failed: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Connected to MySQL</p>";
        
        // Try to create database
        $result = $mysqli->query("CREATE DATABASE IF NOT EXISTS tay99672_qlss");
        if ($result) {
            echo "<p style='color: green;'>✅ Database tay99672_qlss created/verified successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create database: " . $mysqli->error . "</p>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Check completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
