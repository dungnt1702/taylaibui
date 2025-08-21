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
  <!-- Automatically redirect to login.php after 2 seconds -->
  <meta http-equiv="refresh" content="2;url=login.php">
</head>
<body>
  <script>
    // Show a farewell alert to the user
    alert('Hẹn gặp lại');
  </script>
  <p style="text-align:center; margin-top:40px; font-size:18px;">Hẹn gặp lại</p>
</body>
</html>