<?php
require_once 'config.php';
header('Content-Type: application/json');

// Simple API to check if a phone number exists in users table
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
if ($phone === '') {
    echo json_encode(['exists' => false]);
    exit;
}

// Use $mysqli connection from db.php
// Wrap prepare in a try/catch to handle potential errors such as missing table
try {
    $stmt = $mysqli->prepare('SELECT name FROM users WHERE phone = ? AND is_active = 1');
    if ($stmt) {
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $stmt->bind_result($name);
        if ($stmt->fetch()) {
            echo json_encode(['exists' => true, 'name' => $name]);
        } else {
            echo json_encode(['exists' => false]);
        }
        $stmt->close();
    } else {
        // If prepare fails (e.g. table doesn't exist), return false gracefully
        echo json_encode(['exists' => false]);
    }
} catch (Exception $e) {
    // On any exception, return false
    echo json_encode(['exists' => false]);
}
?>