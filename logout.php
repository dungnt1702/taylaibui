<?php
// End the session and show a farewell message before redirecting to the login page.
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng xuất</title>
</head>
<body>
  <script>
    // Show a farewell alert and then redirect immediately to the login page
    alert('Hẹn gặp lại');
    window.location.href = 'login.php';
  </script>
</body>
</html>