<?php
require_once 'db.php';
session_start();
// Determine if the user is logged in; used to decide whether to show the login modal
$requiresLogin = !isset($_SESSION['user_id']);
// Show greeting alert after successful login
$greetingScript = '';
if (!$requiresLogin && isset($_SESSION['greet']) && $_SESSION['greet'] === true) {
  $username = $_SESSION['user_name'] ?? '';
  $greetingScript = "<script>alert('Chào mừng {$username} vào hệ thống quản lý xe của TAY LÁI BỤI Sóc Sơn');</script>";
  unset($_SESSION['greet']);
}
// Determine filter from query
$filter = $_GET['filter'] ?? 'all';
// Determine if the current user is an admin. This will be used to decide which
// user page link to output in the header.
$isAdminUser = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$userPageLink = $isAdminUser ? 'user_manager.php' : 'user_profile.php';
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
  <!-- Header containing menu toggle, logo, site title and user info -->
  <header>
    <div class="header-top">
      <!-- Mobile menu toggle button -->
      <button id="menu-toggle" class="menu-toggle">☰</button>
      <!-- Logo always visible -->
      <img src="logo.png" alt="TLB" class="header-logo" />
      <!-- Site name: displayed on one line and scaled via CSS on mobile -->
      <h1>TAY LÁI BỤI SÓC SƠN</h1>
      <!-- User icon; clicking opens either user profile or manager page depending on role -->
      <a href="<?= $userPageLink ?>" class="user-info">
        <!-- Use a playful icon of a boy and girl smiling instead of showing the username -->
        <span class="user-icon">👦👧</span>
      </a>
    </div>
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
  </header>
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

  <!-- Modal để chỉnh sửa tình trạng xe -->
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
  <?php if (!$requiresLogin): ?>
    <script src="script.js"></script>
  <?php endif; ?>
  <?php
    // Output greeting script if available and the user is logged in
    if ($greetingScript) echo $greetingScript;
  ?>
  <?php if ($requiresLogin): ?>
    <!-- Login modal shown when user is not authenticated -->
    <div class="login-modal" id="login-modal" style="display:flex;">
      <div class="login-modal-content">
        <h2>Yêu cầu đăng nhập</h2>
        <p>Bạn cần đăng nhập trước khi sử dụng hệ thống.</p>
        <a href="login.php" class="login-button">Đăng nhập</a>
      </div>
    </div>
  <?php endif; ?>
</body>
</html>
