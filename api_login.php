<?php
require_once 'db.php';
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
        $_SESSION['is_admin'] = $isAdmin;
        $_SESSION['greet'] = true;
        
        // Persistent login cookie (7 days)
        setcookie('user_id', $uid, time() + 7*24*60*60, '/');
        setcookie('user_name', $uname, time() + 7*24*60*60, '/');
        setcookie('is_admin', $isAdmin, time() + 7*24*60*60, '/');
        
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
    echo json_encode(['success' => false, 'message' => 'Người dùng không tồ tại']);
}

$stmt->close();
?>
