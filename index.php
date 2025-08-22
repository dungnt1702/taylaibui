<?php
require_once 'db.php';
session_start();
// Determine if the user is logged in; used to decide whether to show the login modal
// We need to check both session and cookie, and validate the session is still active
$requiresLogin = true; // Default to requiring login

// Check if user has valid session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    $requiresLogin = false;
}

// Check if user has valid remember_login cookie and session expiration
if (isset($_COOKIE['remember_login']) && $_COOKIE['remember_login'] === '1') {
    if (isset($_COOKIE['session_expires'])) {
        $sessionExpires = (int)$_COOKIE['session_expires'];
        
        // Kiểm tra xem session có hết hạn chưa
        if (time() > $sessionExpires) {
            // Session đã hết hạn, xóa tất cả cookie và session
            session_destroy();
            setcookie('remember_login', '', time() - 3600, '/');
            setcookie('session_expires', '', time() - 3600, '/');
            $requiresLogin = true;
        } else if (isset($_SESSION['user_id'])) {
            // Session còn hạn và user đã đăng nhập
            $requiresLogin = false;
        } else {
            // Cookie còn hạn nhưng không có session, cần đăng nhập lại
            $requiresLogin = true;
        }
    } else {
        // Không có session_expires, xử lý như cũ
        if (!isset($_SESSION['user_id'])) {
            $requiresLogin = true;
        } else {
            $requiresLogin = false;
        }
    }
}
// Show greeting alert after successful login
$greetingScript = '';
if (!$requiresLogin && isset($_SESSION['greet']) && $_SESSION['greet'] === true) {
  $username = $_SESSION['user_name'] ?? '';
  $greetingScript = "<script>alert('Chào mừng {$username} vào hệ thống quản lý xe của TAY LÁI BỤI Sóc Sơn');</script>";
  unset($_SESSION['greet']);
}
// Determine filter from query
$filter = $_GET['filter'] ?? 'all';
// Determine if the current user is an admin. This will be used for future functionality.
$isAdminUser = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
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
      <!-- User icon; clicking opens user management/profile -->
      <a href="?filter=user" class="user-info">
        <!-- Use a playful icon of a boy and girl smiling instead of showing the username -->
        <span class="user-icon">😁</span>
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
      <a href="?filter=maintenance" id="tab-maintenance" class="<?= $filter == 'maintenance' ? 'active' : '' ?>">Bảo dưỡng</a>
      <a href="?filter=repair" id="tab-repair" class="<?= $filter == 'repair' ? 'active' : '' ?>">Sửa chữa</a>
    </nav>
  </header>
  <!-- Dynamic page title depending on tab -->
  <h2 id="page-title"></h2>
  <div id="vehicle-list">
    <!-- Vehicle cards will be rendered dynamically by script.js -->
  </div>
  
  <!-- User content area -->
  <div id="user-content" class="user-content" <?= $filter === 'user' ? '' : 'style="display: none;"' ?>>
    <!-- User content will be loaded here -->
  </div>
  
  <!-- Maintenance content area -->
  <div id="maintenance-content" class="maintenance-content" <?= $filter === 'maintenance' ? '' : 'style="display: none;"' ?>>
    <?php if ($filter === 'maintenance'): ?>
      <?php include 'maintenance_history.php'; ?>
    <?php endif; ?>
  </div>
  
  <!-- Repair content area -->
  <div id="repair-content" class="repair-content" <?= $filter === 'repair' ? '' : 'style="display: none;"' ?>>
    <?php if ($filter === 'repair'): ?>
      <?php include 'repair_history.php'; ?>
    <?php endif; ?>
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
  
  <!-- Modal để xem lịch sử sửa chữa -->
  <div id="repair-history-modal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
      <span class="close" onclick="closeRepairHistoryModal()">&times;</span>
      <h2>🔧 Lịch sử sửa chữa xe</h2>
      <div id="repair-history-content">
        <!-- Nội dung lịch sử sẽ được load ở đây -->
      </div>
      <div class="modal-actions">
        <button onclick="closeRepairHistoryModal()">Đóng</button>
      </div>
    </div>
  </div>
  
  <!-- Modal để thêm/sửa sửa chữa -->
  <div id="repair-modal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
      <span class="close" onclick="closeRepairModal()">&times;</span>
      <h2 id="repair-modal-title">➕ Thêm sửa chữa mới</h2>
      
      <form id="repair-form">
        <input type="hidden" id="repair-id" name="repair_id">
        
        <div class="form-row">
          <div class="form-group">
            <label for="vehicle-select">Xe:</label>
            <select id="vehicle-select" name="vehicle_id" required>
              <option value="">Chọn xe</option>
              <!-- Options sẽ được load từ JavaScript -->
            </select>
          </div>
          
          <div class="form-group">
            <label for="repair-type">Loại sửa chữa:</label>
            <select id="repair-type" name="repair_type" required>
              <option value="">Chọn loại</option>
              <option value="Bảo dưỡng">Bảo dưỡng</option>
              <option value="Sửa chữa">Sửa chữa</option>
              <option value="Hỏng hóc">Hỏng hóc</option>
              <option value="Thay thế">Thay thế</option>
              <option value="Khác">Khác</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="repair-description">Mô tả:</label>
            <textarea id="repair-description" name="description" rows="3" placeholder="Mô tả chi tiết sửa chữa..."></textarea>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="repair-cost">Chi phí (VNĐ):</label>
            <input type="number" id="repair-cost" name="cost" min="0" step="1000" placeholder="0">
          </div>
          
          <div class="form-group">
            <label for="repair-date">Ngày sửa chữa:</label>
            <input type="date" id="repair-date" name="repair_date" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="technician">Thợ sửa chữa:</label>
            <input type="text" id="technician" name="technician" placeholder="Tên thợ sửa chữa">
          </div>
          
          <div class="form-group">
            <label for="repair-status">Trạng thái:</label>
            <select id="repair-status" name="status" required>
              <option value="pending">Chờ xử lý</option>
              <option value="in_progress">Đang sửa</option>
              <option value="completed">Hoàn thành</option>
              <option value="cancelled">Đã hủy</option>
            </select>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="submit" id="repair-submit-btn" class="btn-primary">Lưu sửa chữa</button>
          <button type="button" onclick="closeRepairModal()" class="btn-secondary">Hủy</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Always load script.js for basic functionality like menu toggle -->
  <script src="script.js"></script>
  
  <script>
    // Login popup functionality
    function checkPhone() {
      const phoneInput = document.getElementById('phone');
      const phone = phoneInput.value.trim();
      const nameDisplay = document.getElementById('name-display');
      const passwordGroup = document.getElementById('password-group');
      const submitGroup = document.getElementById('submit-group');
      
      // Reset display
      nameDisplay.textContent = '';
      nameDisplay.className = 'name-display';
      passwordGroup.style.display = 'none';
      submitGroup.style.display = 'none';
      
      if (phone === '' || phone.length < 10) {
        return;
      }
      
      // Only fetch when phone has exactly 10 digits
      if (phone.length === 10) {
        fetch('check_user.php?phone=' + encodeURIComponent(phone))
          .then(res => {
            if (!res.ok) {
              return { exists: false };
            }
            return res.json();
          })
          .then(data => {
            if (data && data.exists) {
              // Show greeting with bold, green name
              nameDisplay.innerHTML = 'Xin chào <strong style="color:#4caf50">' + data.name + '</strong>';
              nameDisplay.className = 'name-display success';
              passwordGroup.style.display = 'block';
              submitGroup.style.display = 'block';
            } else {
              nameDisplay.textContent = 'Người dùng không tồ tại';
              nameDisplay.className = 'name-display error';
            }
          })
          .catch(() => {
            nameDisplay.textContent = '';
            nameDisplay.className = 'name-display';
          });
      }
    }
    
    function togglePasswordVisibility() {
      const pwdInput = document.getElementById('password');
      if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
      } else {
        pwdInput.type = 'password';
      }
    }
    
    // Handle login form submission
    document.addEventListener('DOMContentLoaded', function() {
      const loginForm = document.getElementById('login-form');
      if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const formData = new FormData(loginForm);
          const errorDiv = document.getElementById('login-error');
          
          // Hide previous error
          errorDiv.style.display = 'none';
          
          fetch('api_login.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              // Login successful - reload page to show main content
              window.location.reload();
            } else {
              // Show error message
              errorDiv.textContent = data.message;
              errorDiv.style.display = 'block';
            }
          })
          .catch(error => {
            errorDiv.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
            errorDiv.style.display = 'block';
          });
        });
      }
    });
  </script>
  <?php
    // Output greeting script if available and the user is logged in
    if ($greetingScript) echo $greetingScript;
  ?>
  <?php if ($requiresLogin): ?>
    <!-- Login modal shown when user is not authenticated -->
    <div class="login-modal" id="login-modal" style="display:flex;">
      <div class="login-modal-content">
        <h2>Đăng nhập hệ thống</h2>
        <form id="login-form" class="login-form">
          <div class="form-group">
            <label for="phone">Số điện thoại:</label>
            <input type="text" id="phone" name="phone" oninput="checkPhone()" autocomplete="off" required />
            <div id="name-display" class="name-display"></div>
          </div>
          <div class="form-group" id="password-group" style="display:none;">
            <label for="password">Mật mã:</label>
            <div class="password-wrapper">
              <input type="password" id="password" name="password" autocomplete="off" required />
              <span class="toggle-password" onclick="togglePasswordVisibility()">👁</span>
            </div>
          </div>
          <div class="form-group" id="submit-group" style="display:none;">
            <div class="remember-login">
              <input type="checkbox" id="remember-login" name="remember_login" value="1">
              <label for="remember-login">Ghi nhớ đăng nhập trong 1 tuần</label>
            </div>
            <button type="submit" class="login-btn">Đăng nhập</button>
          </div>
          <div id="login-error" class="error-message" style="display:none;"></div>
        </form>
      </div>
    </div>
  <?php endif; ?>
  
  <!-- Modal thông báo -->
  <div id="message-modal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="message-content">
        <div class="message-icon">✅</div>
        <p id="message-text"></p>
      </div>
      <div class="modal-actions">
        <button onclick="closeMessageModal()" class="btn-primary">Đóng</button>
      </div>
    </div>
  </div>
</body>
</html>
