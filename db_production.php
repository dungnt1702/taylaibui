<?php
// Database configuration for PRODUCTION server
// This file contains production database credentials

// Production database settings
$db_host = 'localhost';
$db_user = 'tay99672_qlss';
$db_pass = '5nW1$m6u3';
$db_name = 'tay99672_qlss';

// Create connection
try {
    global $mysqli;
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($mysqli->connect_error) {
        error_log("Production database connection failed: " . $mysqli->connect_error);
        return false;
    }
    
    // Set charset to utf8
    if (!$mysqli->set_charset("utf8")) {
        error_log("Failed to set charset: " . $mysqli->error);
    }
    
    // Set timezone
    if (!$mysqli->query("SET time_zone = '+07:00'")) {
        error_log("Failed to set timezone: " . $mysqli->error);
    }
    
    // Log successful connection
    error_log("Production database connection successful - Host: " . $mysqli->host_info);
    
} catch (Exception $e) {
    error_log("Exception creating production mysqli: " . $e->getMessage());
    return false;
}

// Verify $mysqli is set
if (!isset($mysqli)) {
    error_log("$mysqli variable is not set after creation");
    return false;
}

if (!($mysqli instanceof mysqli)) {
    error_log("$mysqli is not a mysqli object");
    return false;
}

// Return true if everything is successful
return true;
?>
