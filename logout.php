<?php
// Bắt đầu session để có thể xóa nó
session_start();

// Xóa tất cả session variables
$_SESSION = array();

// Xóa session cookie nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Xóa tất cả cookies liên quan đến đăng nhập và ghi nhớ
$cookies_to_delete = [
    'remember_login',
    'session_expires', 
    'user_id',
    'user_name',
    'is_admin'
];

foreach ($cookies_to_delete as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', time() - 3600, '/');
        setcookie($cookie_name, '', time() - 3600, '/', '', true, true); // Secure and HTTP-only
    }
}

// Hủy session
session_destroy();

// Xóa session ID
if (session_id()) {
    session_regenerate_id(true);
}

// Redirect về trang chính với thông báo đã đăng xuất
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng xuất</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .logout-container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logout-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .logout-message {
            color: #333;
            margin-bottom: 20px;
        }
        .redirect-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .manual-link {
            color: #2196f3;
            text-decoration: none;
        }
        .manual-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">👋</div>
        <div class="logout-message">Đã đăng xuất thành công!</div>
        <div class="redirect-info">Bạn sẽ được chuyển về trang chính trong <span id="countdown">3</span> giây...</div>
        <div>Hoặc <a href="index.php" class="manual-link">click vào đây</a> để chuyển ngay</div>
    </div>

    <script>
        // Countdown và redirect
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                // Redirect về trang chính
                window.location.href = 'index.php';
            }
        }, 1000);
        
        // Redirect ngay lập tức nếu user click vào link
        document.querySelector('.manual-link').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'index.php';
        });
    </script>
</body>
</html>