<?php
// Example admin-only user management page
require_once 'db.php';
session_start();
// Only allow access for logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Determine embed mode
$embedded = defined('EMBEDDED') && EMBEDDED;
// Display heading early when embedded
if ($embedded) {
    echo '<h2>Quản lý người dùng</h2>';
}
// -- xử lý CRUD ở đây --
$message = $message ?? '';
?>
<?php if (!$embedded): ?>
<a href="index.php">← Trang chủ</a>
<h2>Quản lý người dùng</h2>
<?php endif; ?>
<div class="user-container">
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <!-- Form thêm/cập nhật người dùng (giản lược) -->
    <form method="post" class="user-form">
        <label>Số điện thoại: <input type="text" name="phone" required></label>
        <label>Tên: <input type="text" name="name" required></label>
        <label>Mật khẩu: <input type="password" name="password"></label>
        <label><input type="checkbox" name="is_admin"> Quản trị viên</label>
        <label><input type="checkbox" name="is_active" checked> Kích hoạt</label>
        <button type="submit" name="action" value="add">Thêm</button>
    </form>
    <!-- Danh sách người dùng -->
    <h3>Danh sách người dùng</h3>
    <table class="user-table">
        <thead>
            <tr><th>ID</th><th>Số điện thoại</th><th>Tên</th><th>Quản trị</th><th>Kích hoạt</th><th>Mật khẩu mới</th><th>Hành động</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users ?? [] as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['phone']) ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= $u['is_admin'] ? 'Có' : 'Không' ?></td>
                <td><?= $u['is_active'] ? 'Có' : 'Không' ?></td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                        <input type="password" name="password" placeholder="Mật khẩu mới">
                        <button type="submit" name="action" value="update">Lưu</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                        <button type="submit" name="action" value="delete">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
