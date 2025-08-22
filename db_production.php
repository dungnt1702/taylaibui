<?php
// Database configuration for PRODUCTION server
// This file contains production database credentials

// Production database settings
$db_host = 'localhost'; // Change this to your production database host
$db_user = 'your_production_username'; // Change this to your production username
$db_pass = 'your_production_password'; // Change this to your production password
$db_name = 'your_production_database'; // Change this to your production database name

// Create connection
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset to utf8
$mysqli->set_charset("utf8");

// Set timezone
$mysqli->query("SET time_zone = '+07:00'");

// Optional: Enable error reporting for production debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
?>
