<?php
echo "<h1>🔍 Debug db.php Step by Step</h1>";

// Test 1: Check if we can connect to MySQL without database
echo "<h2>Test 1: Connect to MySQL without database</h2>";
try {
    $temp_mysqli = new mysqli('localhost', 'root', '');
    if ($temp_mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Connection to MySQL failed: " . $temp_mysqli->connect_error . "</p>";
        die("Cannot continue without MySQL connection");
    } else {
        echo "<p style='color: green;'>✅ Connected to MySQL successfully</p>";
        echo "<p>Host: " . $temp_mysqli->host_info . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    die("Cannot continue");
}

echo "<hr>";

// Test 2: Check if database exists
echo "<h2>Test 2: Check if database tay99672_qlss exists</h2>";
$result = $temp_mysqli->query("SHOW DATABASES LIKE 'tay99672_qlss'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Database tay99672_qlss exists</p>";
} else {
    echo "<p style='color: red;'>❌ Database tay99672_qlss does NOT exist</p>";
    
    // Try to create database
    echo "<p>Attempting to create database...</p>";
    if ($temp_mysqli->query("CREATE DATABASE tay99672_qlss")) {
        echo "<p style='color: green;'>✅ Database tay99672_qlss created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create database: " . $temp_mysqli->error . "</p>";
        die("Cannot continue");
    }
}

echo "<hr>";

// Test 3: Try to connect to the specific database
echo "<h2>Test 3: Connect to database tay99672_qlss</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '', 'tay99672_qlss');
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ Connection to tay99672_qlss failed: " . $mysqli->connect_error . "</p>";
        die("Cannot continue");
    } else {
        echo "<p style='color: green;'>✅ Connected to tay99672_qlss successfully</p>";
        echo "<p>Host: " . $mysqli->host_info . "</p>";
        echo "<p>Database: " . $mysqli->database . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    die("Cannot continue");
}

echo "<hr>";

// Test 4: Check and create tables
echo "<h2>Test 4: Check and create tables</h2>";
$tables = [
    'users' => "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100),
        password VARCHAR(255),
        is_admin TINYINT DEFAULT 0,
        is_active TINYINT DEFAULT 1
    )",
    'vehicles' => "CREATE TABLE vehicles (
        id INT PRIMARY KEY,
        active TINYINT DEFAULT 1,
        endAt BIGINT,
        paused TINYINT DEFAULT 0,
        remaining INT,
        minutes INT,
        notifiedEnd TINYINT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        routeNumber TINYINT UNSIGNED DEFAULT 0,
        routeStartAt BIGINT UNSIGNED DEFAULT 0,
        repairNotes TEXT
    )",
    'log' => "CREATE TABLE log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        xe INT,
        bat_dau DATETIME,
        ket_thuc DATETIME,
        thoi_gian INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'repair_history' => "CREATE TABLE repair_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT,
        repair_type VARCHAR(100),
        description TEXT,
        cost DECIMAL(10,2) DEFAULT 0,
        repair_date DATE,
        completed_date DATE NULL,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        technician VARCHAR(100),
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by VARCHAR(100) DEFAULT 'N/A'
    )",
    'maintenance_history' => "CREATE TABLE maintenance_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT,
        status VARCHAR(100),
        notes TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $table_name => $create_sql) {
    echo "<h3>Checking table: $table_name</h3>";
    $result = $mysqli->query("SHOW TABLES LIKE '$table_name'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table $table_name exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table $table_name does NOT exist</p>";
        echo "<p>Creating table $table_name...</p>";
        
        if ($mysqli->query($create_sql)) {
            echo "<p style='color: green;'>✅ Table $table_name created successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create table $table_name: " . $mysqli->error . "</p>";
        }
    }
}

echo "<hr>";

// Test 5: Verify $mysqli is still working
echo "<h2>Test 5: Verify \$mysqli is working</h2>";
if (isset($mysqli) && !$mysqli->connect_error) {
    echo "<p style='color: green;'>✅ \$mysqli is set and working</p>";
    
    // Test a simple query
    $result = $mysqli->query("SELECT 1 as test");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Test query successful: " . $row['test'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Test query failed: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ \$mysqli is NOT working</p>";
    if (isset($mysqli)) {
        echo "<p>Error: " . $mysqli->connect_error . "</p>";
    }
}

echo "<hr>";

// Test 6: Show final status
echo "<h2>Test 6: Final Status</h2>";
if (isset($mysqli) && !$mysqli->connect_error) {
    echo "<p style='color: green;'>✅ Database setup completed successfully!</p>";
    echo "<p>You can now use \$mysqli in your application.</p>";
} else {
    echo "<p style='color: red;'>❌ Database setup failed!</p>";
    echo "<p>Please check the errors above.</p>";
}

echo "<hr>";
echo "<p><em>Debug completed at: " . date('Y-m-d H:i:s') . "</em></p>";

// Clean up
if (isset($temp_mysqli)) $temp_mysqli->close();
if (isset($mysqli)) $mysqli->close();
?>
