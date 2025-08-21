<?php
// Example user profile page
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$embedded = defined('EMBEDDED') && EMBEDDED;
// Show heading when embedded
if ($embedded) {
    echo '<h2>Thông tin cá nhân</h2>';
}
// Fetch current user data
$uid = $_SESSION['user_id'];
$phone = '';
$name = '';
$stmt = $mysqli->prepare('SELECT phone, name FROM users WHERE id=?');
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($phone, $name);
    $stmt->fetch();
    $stmt->close();
}
$message = $message ?? '';
?>
<?php if (!$embedded): ?>
<a href="index.php">← Trang chủ</a>
<h2>Thông tin cá nhân</h2>
<?php endif; ?>
<div class="profile-container">
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Số điện thoại:
            <input type="text" value="<?= htmlspecialchars($phone) ?>" disabled>
        </label>
        <label>Tên:
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
        </label>
        <label>Mật khẩu mới:
            <input type="password" name="password">
        </label>
        <button type="submit" name="action" value="update">Lưu thay đổi</button>
    </form>
</div>
