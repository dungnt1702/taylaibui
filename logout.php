<?php
// Destroy the current session and redirect to login page
session_start();
session_destroy();
// Redirect to login page or home page depending on your authentication flow
header('Location: login.php');
exit;