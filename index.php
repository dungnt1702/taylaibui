<?php
require_once 'db.php';
session_start();
// Redirect to login page if not authenticated
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// Show greeting alert after successful login
$greetingScript = '';
if (isset($_SESSION['greet']) && $_SESSION['greet'] === true) {
  $username = $_SESSION['user_name'] ?? '';
  $greetingScript = "<script>alert('Chào mừng {$username} vào hệ thống quản lý xe của TAY LÁI BỤI Sóc Sơn');</script>";
  unset($_SESSION['greet']);
}
// Determine filter from query
$filter = $_GET['filter'] ?? 'all';

// Determine admin status
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Determine if the user info page should be displayed
// When filter=user, instead of redirecting to a separate file we will embed
// the appropriate user page (manager or profile) directly into this page.
$showUserPage = false;
if ($filter === 'user') {
  $showUserPage = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <!-- Enable responsive design on mobile devices -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Set the document title to the site name only -->
  <title>TAY LÁI BỤI SÓC SƠN</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <!-- Header containing menu toggle, site title and user info -->
  <header>
    <div class="header-top">
      <!-- Left side: menu toggle and logo -->
      <div class="header-left">
        <button id="menu-toggle" class="menu-toggle">☰</button>
        <img src="logo.png" alt="TLB" class="header-logo" />
      </div>
      <!-- Site name: displayed on one line and scaled via CSS on mobile -->
      <h1>TAY LÁI BỤI SÓC SƠN</h1>
      <?php $userName = htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
      <!-- User info link uses filter=user; redirect handled above -->
      <a href="?filter=user" class="user-info">
        <span class="user-icon">👤</span>
        <span class="user-name"><?= $userName ?></span>
      </a>
    </div>
    <nav id="nav-menu" class="nav-menu">
      <!-- No logo in the mobile menu; the list starts right below the header -->
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
  </header>
  <!-- Dynamic page title depending on tab -->
  <?php if ($showUserPage): ?>
    <?php // When showing user page, embed the relevant user page content instead of the vehicle list ?>
    <?php define('EMBEDDED', true); ?>
    <?php
      if ($isAdmin) {
        include 'user_manager.php';
      } else {
        include 'user_profile.php';
      }
    ?>
  <?php else: ?>
    <!-- Dynamic page title depending on tab -->
    <h2 id="page-title"></h2>
    <div id="vehicle-list">
      <!-- Vehicle cards will be rendered dynamically by script.js -->
    </div>
    <!-- Controls for group (khách đoàn) actions -->
    <div id="group-controls" class="group-controls">
      <!-- Dòng chọn thời gian -->
      <div class="group-control">
        <select id="group-timer">
          <option value="30" selected>30 phút</option>
          <option value="45">45 phút</option>
        </select>
        <button onclick="startTimerGroup(parseInt(document.getElementById('group-timer').value, 10))">Chọn thời gian</button>
      </div>
      <!-- Dòng chọn cung đường -->
      <div class="group-control">
        <select id="group-route">
          <option value="0">--</option>
<?php for ($i=1; $i<=10; $i++): ?>
        <option value="<?= $i ?>"><?= $i ?></option>
<?php endfor; ?>
        </select>
        <button onclick="setRouteGroup(parseInt(document.getElementById('group-route').value, 10))">Chọn cung đường</button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Modal để chỉnh sửa tình trạng xe (remains on all pages for consistency) -->
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
  <script src="script.js"></script>
  <?php
    // Output greeting script if available
    if ($greetingScript) echo $greetingScript;
  ?>
</body>
</html>
