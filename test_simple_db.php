<?php
echo "<h1>🔍 Test Simple Database Connection</h1>";

// Test 1: Connect to MySQL without database
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
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Try to create database
echo "<h2>Test 2: Create database tay99672_qlss</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '');
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Connection failed: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Connected to MySQL</p>";
        
        // Create database
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

// Test 3: Connect to the created database
echo "<h2>Test 3: Connect to tay99672_qlss</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '', 'tay99672_qlss');
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Connection to tay99672_qlss failed: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Connection to tay99672_qlss successful</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
        
        // Show tables
        $result = $mysqli->query("SHOW TABLES");
        if ($result) {
            echo "<h3>Tables in tay99672_qlss:</h3>";
            if ($result->num_rows == 0) {
                echo "<p>No tables found. Database is empty.</p>";
            } else {
                echo "<ul>";
                while ($row = $result->fetch_assoc()) {
                    echo "<li>" . $row['Tables_in_tay99672_qlss'] . "</li>";
                }
                echo "</ul>";
            }
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
