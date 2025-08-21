<?php
require_once 'db.php';
session_start();

// If user already logged in via session, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check persistent login via cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id']) && isset($_COOKIE['user_name'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['user_name'] = $_COOKIE['user_name'];
    if (isset($_COOKIE['is_admin'])) {
        $_SESSION['is_admin'] = $_COOKIE['is_admin'];
    }
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($phone !== '' && $password !== '') {
        $stmt = $mysqli->prepare('SELECT id, name, password, is_admin, is_active FROM users WHERE phone = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $stmt->bind_result($uid, $uname, $pwdHash, $isAdmin, $isActive);
        if ($stmt->fetch()) {
            // Accept login if account active and either:
            // 1) password_verify matches the hashed password
            // 2) the stored password matches plain text (fallback)
            // 3) for the default admin phone (0943036579), allow the known plain password
            $valid = false;
            if ($isActive == 1) {
                if (password_verify($password, $pwdHash)) {
                    $valid = true;
                } elseif ($password === $pwdHash) {
                    // Fallback: stored password may be plain text (e.g. when added via user manager)
                    $valid = true;
                } elseif ($phone === '0943036579' && $password === '@TayLaiBui193#') {
                    // Allow default admin password if hash mismatches (to aid initial login)
                    $valid = true;
                }
            }
            if ($valid) {
                // Success: set session and cookies
                $_SESSION['user_id'] = $uid;
                $_SESSION['user_name'] = $uname;
                $_SESSION['is_admin'] = $isAdmin;
                // Persistent login cookie (7 days)
                setcookie('user_id', $uid, time() + 7*24*60*60, '/');
                setcookie('user_name', $uname, time() + 7*24*60*60, '/');
                setcookie('is_admin', $isAdmin, time() + 7*24*60*60, '/');
                $_SESSION['greet'] = true;
                $stmt->close();
                header('Location: index.php');
                exit;
            } else {
                $error = 'Mật mã không đúng hoặc tài khoản bị khóa.';
            }
        } else {
            $error = 'Người dùng không tồn tại.';
        }
        $stmt->close();
    } else {
        $error = 'Vui lòng nhập số điện thoại và mật mã.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập</title>
  <link rel="stylesheet" href="style.css" />
  <script>
    function togglePasswordVisibility() {
      const pwdInput = document.getElementById('password');
      if (!pwdInput) return;
      if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
      } else {
        pwdInput.type = 'password';
      }
    }
    function checkPhone() {
      const phoneInput = document.getElementById('phone');
      const phone = phoneInput.value.trim();
      const nameSpan = document.getElementById('name-display');
      const nameRow = document.getElementById('name-row');
      const passwordRow = document.getElementById('password-row');
      const submitBtn = document.getElementById('submit-btn');
      // Hide name and password fields if phone is empty or not enough digits
      // Only check when phone has exactly 10 digits
      if (phone === '' || phone.length < 10) {
        nameSpan.textContent = '';
        nameSpan.classList.remove('error');
        nameRow.style.display = 'none';
        passwordRow.style.display = 'none';
        submitBtn.style.display = 'none';
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
              nameSpan.innerHTML = 'Xin chào <strong style="color:#4caf50">' + data.name + '</strong>';
              nameSpan.classList.remove('error');
              nameRow.style.display = 'block';
              passwordRow.style.display = 'block';
              submitBtn.style.display = 'block';
            } else {
              nameSpan.textContent = 'Người dùng không tồn tại';
              nameSpan.classList.add('error');
              nameRow.style.display = 'block';
              passwordRow.style.display = 'none';
              submitBtn.style.display = 'none';
            }
          })
          .catch(() => {
            nameSpan.textContent = '';
            nameSpan.classList.remove('error');
            nameRow.style.display = 'none';
            passwordRow.style.display = 'none';
            submitBtn.style.display = 'none';
          });
      } else {
        // Hide fields if more than 10 digits or still typing
        nameSpan.textContent = '';
        nameSpan.classList.remove('error');
        nameRow.style.display = 'none';
        passwordRow.style.display = 'none';
        submitBtn.style.display = 'none';
      }
    }
  </script>
  <style>
    /* Simple login page styling */
    .login-container {
      max-width: 400px;
      margin: 50px auto;
      padding: 20px;
      background: #fff;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border-radius: 8px;
      text-align: left;
    }
    .login-container h2 {
      margin-top: 0;
      text-align: center;
      color: #f57c00;
    }
    .login-container label {
      display: block;
      margin-top: 12px;
    }
    .login-container input {
      width: 100%;
      padding: 8px;
      margin-top: 4px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }
    .login-container .error {
      color: red;
    }
    .login-container button {
      margin-top: 16px;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      background-color: #ff9800;
      color: #fff;
      cursor: pointer;
    }
    .login-container button:hover {
      opacity: 0.9;
    }
    #password-row {
      display: none;
    }
    #submit-btn {
      display: none;
    }
    .error-msg {
      color: red;
      margin-top: 10px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Đăng nhập</h2>
    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <label for="phone">Số điện thoại:</label>
      <input type="text" id="phone" name="phone" oninput="checkPhone()" autocomplete="off" />
      <div id="name-row" style="text-align:center; margin-top: 8px; display:none;">
        <span id="name-display"></span>
      </div>
      <div id="password-row" style="display:none; margin-top: 12px;">
        <label for="password">Mật mã:</label>
        <div class="password-wrapper" style="position: relative;">
          <input type="password" id="password" name="password" autocomplete="off" style="padding-right: 30px;" />
          <span class="toggle-password" onclick="togglePasswordVisibility()" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer;">
            👁
          </span>
        </div>
      </div>
      <div class="button-row" style="display:flex; justify-content: space-between; margin-top: 16px;">
        <button type="submit" id="submit-btn" style="display:none; flex: 1; margin-right: 8px; background-color:#2196f3; color:#fff;">Đăng nhập</button>
        <button type="button" onclick="window.location.href='index.php'" style="flex: 1; background-color:#e0e0e0; color:#444;">Đóng</button>
      </div>
    </form>
  </div>
</body>
</html>