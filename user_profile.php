<?php
require_once 'db.php';
session_start();

// Redirect to login if user not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];
$message = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    if ($newName === '') {
        $message = 'Tên không được để trống.';
    } else {
        // Prepare update statement based on whether password is provided
        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('UPDATE users SET name=?, password=? WHERE id=?');
            $stmt->bind_param('ssi', $newName, $hash, $uid);
        } else {
            $stmt = $mysqli->prepare('UPDATE users SET name=? WHERE id=?');
            $stmt->bind_param('si', $newName, $uid);
        }
        if ($stmt && $stmt->execute()) {
            $message = 'Cập nhật thành công.';
            // Update session name so header displays new name
            $_SESSION['user_name'] = $newName;
        } else {
            $message = 'Lỗi khi cập nhật thông tin.';
        }
        if ($stmt) $stmt->close();
    }
}

// Determine if embedded
$embedded = defined('EMBEDDED') && EMBEDDED;
// Fetch current user info to display in form
$phone = '';
$name = '';
$stmt = $mysqli->prepare('SELECT phone, name FROM users WHERE id=?');
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($phone, $name);
    $stmt->fetch();
    $stmt->close();
}
?>
<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thông tin cá nhân</title>
  <link rel="stylesheet" href="style.css">
  <style>
<?php endif; ?>
    .profile-container {
      max-width: 500px;
      margin: 50px auto;
      padding: 20px;
      background: #ffffff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      border-radius: 8px;
    }
    .profile-container h2 {
      margin-top: 0;
      text-align: center;
      color: #f57c00;
    }
    .profile-container label {
      display: block;
      margin-top: 12px;
      font-weight: bold;
    }
    .profile-container input {
      width: 100%;
      padding: 8px;
      margin-top: 4px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }
    .profile-container button {
      margin-top: 20px;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      background-color: #2196f3;
      color: #fff;
      cursor: pointer;
      width: 100%;
    }
    .profile-container button:hover {
      opacity: 0.9;
    }
    .message {
      color: green;
      margin-top: 10px;
      text-align: center;
    }
<?php if (!$embedded): ?>
  </style>
</head>
<body>
<?php else: ?>
  </style>
<?php endif; ?>
  <div class="profile-container">
    <div style="text-align:left; margin-bottom:10px;">
      <a href="index.php" style="color:#2196f3;text-decoration:none; font-weight:bold;">&larr; Trang chủ</a>
    </div>
    <h2>Thông tin cá nhân</h2>
    <?php if ($message !== ''): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" action="user_profile.php">
      <label>Số điện thoại:
        <input type="text" value="<?= htmlspecialchars($phone) ?>" disabled>
      </label>
      <label>Tên:
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
      </label>
      <label>Mật khẩu mới:
        <input type="password" name="password" placeholder="Để trống nếu giữ nguyên">
      </label>
      <button type="submit">Lưu thay đổi</button>
    </form>
  </div>
<?php if (!$embedded): ?>
</body>
</html>
<?php endif; ?>