<?php
// This file simply redirects the user to the correct page depending on their role.
session_start();
// If neither user_id nor user_name is present, redirect to login page
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name'])) {
  header('Location: login.php');
  exit;
}
// If admin, go to manager page; else to profile page
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
  header('Location: user_manager.php');
  exit;
}
header('Location: user_profile.php');
exit;