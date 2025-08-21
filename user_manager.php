<?php
require_once 'db.php';
session_start();
// Require authentication
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// If the user is not an admin, redirect to their profile page
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
  header('Location: user_profile.php');
  exit;
}

$username = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý người dùng</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="header-top">
      <button id="menu-toggle" class="menu-toggle">☰</button>
      <img src="logo.png" alt="TLB" class="header-logo">
      <h1>Quản lý người dùng</h1>
      <!-- Show user info linking to this manager page -->
      <a href="user_manager.php" class="user-info">
        <span class="user-icon">😺</span>
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
  <div class="user-container">
    <h2>Quản lý người dùng</h2>
    <p>Chức năng quản lý người dùng đang được phát triển.</p>
    <a href="logout.php" class="logout-btn">Đăng xuất</a>
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
</body>
</html>