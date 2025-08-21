<?php
require_once 'db.php';
session_start();
// Determine if the user is logged in. If not, we'll show a login modal instead of redirecting.
$requiresLogin = !(isset($_SESSION['user_id']) || isset($_COOKIE['remember_login']));
// If logged in and user is admin, redirect to the manager page
if (!$requiresLogin && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
  header('Location: user_manager.php');
  exit;
}

$username = !$requiresLogin ? ($_SESSION['user_name'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thông tin cá nhân</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="header-top">
      <button id="menu-toggle" class="menu-toggle">☰</button>
      <img src="logo.png" alt="TLB" class="header-logo">
      <h1>Thông tin cá nhân</h1>
      <!-- Show user icon; link back to this page -->
      <a href="user_profile.php" class="user-info">
        <!-- Use a boy and girl smiling icon instead of showing the username -->
        <span class="user-icon">😁</span>
      </a>
    </div>
    <nav id="nav-menu" class="nav-menu">
      <a href="index.php?filter=all">Tất cả xe</a>
      <a href="index.php?filter=inactive">Xe trong xưởng</a>
      <a href="index.php?filter=active">Xe ngoài bãi</a>
      <a href="index.php?filter=running">Xe đang chạy</a>
      <a href="index.php?filter=waiting">Xe đang chờ</a>
      <a href="index.php?filter=expired">Xe hết giờ</a>
      <a href="index.php?filter=paused">Xe tạm dừng</a>
      <a href="index.php?filter=route">Xe cung đường</a>
      <a href="index.php?filter=group">Khách đoàn</a>
    </nav>
  </header>
  <?php if ($requiresLogin): ?>
    <!-- Show login modal when user is not authenticated -->
    <div class="login-modal" id="login-modal" style="display:flex;">
      <div class="login-modal-content">
        <h2>Yêu cầu đăng nhập</h2>
        <p>Bạn cần đăng nhập trước khi sử dụng hệ thống.</p>
        <a href="login.php" class="login-button">Đăng nhập</a>
      </div>
    </div>
  <?php else: ?>
    <div class="user-container">
      <h2>Thông tin cá nhân</h2>
      <form>
        <label for="username">Tên người dùng:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" readonly>
        <button type="button">Cập nhật thông tin</button>
        <a href="logout.php" class="logout-btn">Đăng xuất</a>
      </form>
    </div>
    <!-- Minimal script to toggle navigation -->
    <script>
      function toggleNav() {
        const nav = document.getElementById('nav-menu');
        if (nav) nav.classList.toggle('open');
      }
      const menuToggle = document.getElementById('menu-toggle');
      if (menuToggle) menuToggle.addEventListener('click', toggleNav);
      document.addEventListener('click', function(e) {
        const nav = document.getElementById('nav-menu');
        const toggle = document.getElementById('menu-toggle');
        if (!nav || !toggle) return;
        if (nav.classList.contains('open')) {
          if (!nav.contains(e.target) && e.target !== toggle) {
            nav.classList.remove('open');
          }
        }
      });
    </script>
  <?php endif; ?>
</body>
</html>