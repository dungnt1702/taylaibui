<?php
require_once 'db.php';
session_start();
// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$username = $_SESSION['user_name'] ?? '';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Determine the page heading based on user role
$pageTitle = $isAdmin ? 'Quản lý người dùng' : 'Thông tin cá nhân';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="header-top">
      <button id="menu-toggle" class="menu-toggle">☰</button>
      <img src="logo.png" alt="TLB" class="header-logo">
      <h1><?= htmlspecialchars($pageTitle) ?></h1>
      <!-- Show user info but do not link to the same page -->
      <div class="user-info">
        <span class="user-icon">👤</span>
        <span class="user-name"><?= htmlspecialchars($username) ?></span>
      </div>
    </div>
    <!-- Navigation menu to go back to main pages -->
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
  <div class="user-container">
    <?php if ($isAdmin): ?>
      <h2>Trang quản lý người dùng</h2>
      <p>Chức năng quản lý người dùng đang được phát triển.</p>
      <a href="logout.php" class="logout-btn">Đăng xuất</a>
    <?php else: ?>
      <h2>Thông tin cá nhân</h2>
      <!-- Display basic user information; you can extend this form with more fields -->
      <form>
        <label for="username">Tên người dùng:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" readonly>
        <!-- Update button (placeholder) -->
        <button type="button">Cập nhật thông tin</button>
        <a href="logout.php" class="logout-btn">Đăng xuất</a>
      </form>
    <?php endif; ?>
  </div>
  <!-- Only include a lightweight script to toggle the mobile navigation. Avoid loading the full vehicle manager script on this page -->
  <script>
    function toggleNav() {
      const nav = document.getElementById('nav-menu');
      if (nav) nav.classList.toggle('open');
    }
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
      menuToggle.addEventListener('click', toggleNav);
    }
    // Close the nav menu when clicking outside of it on mobile
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
</body>
</html>