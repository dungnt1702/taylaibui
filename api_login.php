<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

// Nếu đã đăng nhập, trả về thông báo
if (isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã đăng nhập rồi']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($phone === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập số điện thoại và mật mã']);
    exit;
}

// Kiểm tra người dùng
$stmt = $mysqli->prepare('SELECT id, name, password, is_admin, is_active FROM users WHERE phone = ?');
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->bind_result($uid, $uname, $pwdHash, $isAdmin, $isActive);

if ($stmt->fetch()) {
    // Kiểm tra mật khẩu
    $valid = false;
    if ($isActive == 1) {
        if (password_verify($password, $pwdHash)) {
            $valid = true;
        } elseif ($password === $pwdHash) {
            // Fallback: stored password may be plain text
            $valid = true;
        } elseif ($phone === '0943036579' && $password === '@TayLaiBui193#') {
            // Allow default admin password
            $valid = true;
        }
    }
    
    if ($valid) {
        // Đăng nhập thành công
        $_SESSION['user_id'] = $uid;
        $_SESSION['user_name'] = $uname;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['is_admin'] = $isAdmin;
        $_SESSION['greet'] = true;
        
        // Xử lý checkbox "Ghi nhớ đăng nhập"
        $rememberLogin = isset($_POST['remember_login']) && $_POST['remember_login'] === '1';
        
        if ($rememberLogin) {
            // Nếu check vào checkbox: lưu phiên đăng nhập trong 7 ngày
            $expires = time() + (7 * 24 * 60 * 60); // 7 days
            setcookie('user_id', $uid, $expires, '/');
            setcookie('user_name', $uname, $expires, '/');
            setcookie('is_admin', $isAdmin, $expires, '/');
            setcookie('session_expires', $expires, $expires, '/');
        }
        // Nếu không check: chỉ dùng session thông thường (không tạo cookie)
        
        $stmt->close();
        echo json_encode([
            'success' => true, 
            'message' => 'Đăng nhập thành công',
            'user_name' => $uname,
            'is_admin' => $isAdmin
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Mật mã không đúng hoặc tài khoản bị khóa']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
}

$stmt->close();
?>
