<?php
require_once 'db.php';
session_start();
// Only admin can access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
  echo 'Access denied';
  return;
}

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($action === 'add') {
        $password = trim($_POST['password'] ?? '');
        if ($phone !== '' && $name !== '' && $password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (phone, name, password, is_admin, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssii', $phone, $name, $hash, $isAdmin, $isActive);
            if ($stmt->execute()) {
                $message = 'Thêm người dùng thành công.';
            } else {
                $message = 'Lỗi: không thể thêm người dùng. Có thể số điện thoại đã tồn tại.';
            }
            $stmt->close();
        } else {
            $message = 'Vui lòng nhập đầy đủ thông tin khi thêm.';
        }
    } elseif ($action === 'update') {
        $uid = intval($_POST['uid'] ?? 0);
        $password = trim($_POST['password'] ?? '');
        if ($uid > 0 && $phone !== '' && $name !== '') {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('UPDATE users SET phone=?, name=?, password=?, is_admin=?, is_active=? WHERE id=?');
                $stmt->bind_param('sssiii', $phone, $name, $hash, $isAdmin, $isActive, $uid);
            } else {
                $stmt = $mysqli->prepare('UPDATE users SET phone=?, name=?, is_admin=?, is_active=? WHERE id=?');
                $stmt->bind_param('ssiii', $phone, $name, $isAdmin, $isActive, $uid);
            }
            if ($stmt->execute()) {
                $message = 'Cập nhật thành công.';
            } else {
                $message = 'Lỗi khi cập nhật.';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $uid = intval($_POST['uid'] ?? 0);
        if ($uid > 0) {
            $stmt = $mysqli->prepare('DELETE FROM users WHERE id=?');
            $stmt->bind_param('i', $uid);
            if ($stmt->execute()) {
                $message = 'Xóa người dùng thành công.';
            } else {
                $message = 'Lỗi khi xóa.';
            }
            $stmt->close();
        }
    }
}

// Fetch all users
$users = [];
// Use $mysqli instead of undefined $conn
$result = $mysqli->query('SELECT id, phone, name, is_admin, is_active FROM users ORDER BY id ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

// Determine if this page is being embedded inside another page (e.g. index.php)
$embedded = defined('EMBEDDED') && EMBEDDED;
?>
<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý người dùng</title>
  <link rel="stylesheet" href="style.css" />
  <style>
<?php endif; ?>
    /* Container for user management page */
    .user-container {
      max-width: 900px;
      margin: 20px auto;
      padding: 20px;
      background: #ffffff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      border-radius: 8px;
    }
    .user-container h2, .user-container h3 {
      margin-top: 0;
      color: #f57c00;
      text-align: center;
    }
    /* Form styling */
    .user-form {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }
    .user-form label {
      flex: 1 1 200px;
      font-weight: bold;
    }
    .user-form input[type="text"], .user-form input[type="password"] {
      width: 100%;
      padding: 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }
    .user-form input[type="checkbox"] {
      transform: scale(1.2);
      margin-right: 4px;
    }
    .user-form button {
      padding: 8px 16px;
      font-size: 14px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      background: #2196f3;
      color: #fff;
    }
    .user-form button:hover { opacity: 0.9; }
    .message { color: green; margin-top: 10px; text-align:center; }
    /* Table styling */
    .user-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      overflow-x: auto;
      display: block;
    }
    .user-table thead {
      background: #f0f0f0;
    }
    .user-table th, .user-table td {
      padding: 8px 12px;
      border-bottom: 1px solid #eee;
      text-align: center;
      white-space: nowrap;
    }
    .user-table tr:nth-child(even) {
      background: #fafafa;
    }
    .user-table button {
      padding: 6px 10px;
      font-size: 13px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      background: #2196f3;
      color: #fff;
    }
    .user-table form {
      margin: 0;
    }
    /* Responsive adjustments */
    @media (max-width: 600px) {
      .user-form label {
        flex: 1 1 100%;
      }
      .user-form button {
        width: 100%;
      }
      .user-table th, .user-table td {
        font-size: 12px;
        padding: 6px;
      }
    }
<?php if (!$embedded): ?>
  </style>
</head>
<body>
<?php else: ?>
  </style>
<?php endif; ?>
  <div class="user-container">
    <div style="text-align:left; margin-bottom:10px;">
      <a href="index.php" style="color:#2196f3;text-decoration:none; font-weight:bold;">&larr; Trang chủ</a>
    </div>
    <h2>Quản lý người dùng</h2>
    <?php if ($message): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <h3>Thêm người dùng mới</h3>
    <form method="post" class="user-form">
      <input type="hidden" name="action" value="add" />
      <label>Số điện thoại:<br><input type="text" name="phone" required></label>
      <label>Tên:<br><input type="text" name="name" required></label>
      <label>Mật khẩu:<br><input type="password" name="password" required></label>
      <label><input type="checkbox" name="is_admin"> Quản trị viên</label>
      <label><input type="checkbox" name="is_active" checked> Kích hoạt</label>
      <button type="submit">Thêm</button>
    </form>
    <h3>Danh sách người dùng</h3>
    <table class="user-table">
      <thead>
        <tr><th>ID</th><th>Số điện thoại</th><th>Tên</th><th>Quản trị</th><th>Kích hoạt</th><th>Mật khẩu mới</th><th colspan="2">Hành động</th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <form method="post" class="user-form">
          <td><?= $u['id'] ?></td>
          <td><input type="text" name="phone" value="<?= htmlspecialchars($u['phone']) ?>" required></td>
          <td><input type="text" name="name" value="<?= htmlspecialchars($u['name']) ?>" required></td>
          <td><input type="checkbox" name="is_admin" <?= $u['is_admin'] ? 'checked' : '' ?>></td>
          <td><input type="checkbox" name="is_active" <?= $u['is_active'] ? 'checked' : '' ?>></td>
          <td><input type="password" name="password" placeholder="Để trống nếu giữ nguyên"></td>
          <td>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <button type="submit">Lưu</button>
          </td>
          </form>
          <td>
            <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="uid" value="<?= $u['id'] ?>">
              <button type="submit" style="background:#f44336;">Xóa</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php if (!$embedded): ?>
</body>
</html>
<?php endif; ?>