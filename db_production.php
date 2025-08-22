<?php
// Database configuration for PRODUCTION server
// This file contains production database credentials

// Production database settings
$db_host = 'localhost';
$db_user = 'tay99672_qlss';
$db_pass = '5nW1$m6u3';
$db_name = 'tay99672_qlss';

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
