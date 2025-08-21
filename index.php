<?php
require_once 'db.php';
session_start();
// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// Read filter and admin status
$filter = $_GET['filter'] ?? 'all';
$isAdmin = !empty($_SESSION['is_admin']);
$showUserPage = ($filter === 'user');
// Greeting script after login (optional)
$greetingScript = '';
if (isset($_SESSION['greet']) && $_SESSION['greet'] === true) {
  $username = $_SESSION['user_name'] ?? '';
  $greetingScript = "<script>alert('Chào mừng {$username} vào hệ thống quản lý xe của TAY LÁI BỤI Sóc Sơn');</script>";
  unset($_SESSION['greet']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TAY LÁI BỤI SÓC SƠN</title>
  <!-- Main site stylesheet -->
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="header-left">
      <button id="menu-toggle" class="menu-toggle">&#9776;</button>
      <img src="logo.png" alt="TLB" class="header-logo" />
    </div>
    <h1>TAY LÁI BỤI SÓC SƠN</h1>
    <?php $userName = htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
    <a href="?filter=user" class="user-info">
      <span class="user-icon">👤</span>
      <span class="user-name"><?= $userName ?></span>
    </a>
    <!-- End of header -->
  </header>
  <!-- Navigation -->
  <nav id="nav-menu" class="nav-menu">
    <a href="?filter=all" id="tab-all" class="<?= $filter == 'all' ? 'active' : '' ?>">Tất cả xe</a>
    <a href="?filter=inactive" id="tab-inactive" class="<?= $filter == 'inactive' ? 'active' : '' ?>">Xe trong xưởng</a>
    <a href="?filter=active" id="tab-active" class="<?= $filter == 'active' ? 'active' : '' ?>">Xe ngoài bãi</a>
    <a href="?filter=running" id="tab-running" class="<?= $filter == 'running' ? 'active' : '' ?>">Xe đang chạy</a>
    <a href="?filter=waiting" id="tab-waiting" class="<?= $filter == 'waiting' ? 'active' : '' ?>">Xe đang chờ</a>
    <a href="?filter=expired" id="tab-expired" class="<?= $filter == 'expired' ? 'active' : '' ?>">Xe hết giờ</a>
    <a href="?filter=paused" id="tab-paused" class="<?= $filter == 'paused' ? 'active' : '' ?>">Xe tạm dừng</a>
    <a href="?filter=route" id="tab-route" class="<?= $filter == 'route' ? 'active' : '' ?>">Xe cung đường</a>
    <a href="?filter=group" id="tab-group" class="<?= $filter == 'group' ? 'active' : '' ?>">Khách đoàn</a>
  </nav>

  <?php if ($showUserPage): ?>
    <?php define('EMBEDDED', true); ?>
    <?php
      if ($isAdmin) {
        include 'user_manager.php';
      } else {
        include 'user_profile.php';
      }
    ?>
  <?php else: ?>
    <!-- Vehicle page content here.  This can call your existing script.js to render vehicles -->
    <h2 id="page-title"></h2>
    <div id="vehicle-list"></div>
    <div id="group-controls" class="group-controls">
      <!-- controls for group operations (optional) -->
    </div>
  <?php endif; ?>

  <!-- Modal for editing vehicle status (same as before) -->
  <div id="notes-modal" class="modal">
    <div class="modal-content">
      <h3 id="notes-title">TÌNH TRẠNG XE</h3>
      <textarea id="notes-textarea" rows="5" cols="40"></textarea>
      <div class="modal-actions">
        <button onclick="saveNotes()">Lưu</button>
        <button onclick="closeNotesModal()">Hủy</button>
      </div>
    </div>
  </div>
  <?php if (!$showUserPage): ?>
  <script src="script.js"></script>
  <?php endif; ?>
  <?php if ($greetingScript) echo $greetingScript; ?>
</body>
</html>
