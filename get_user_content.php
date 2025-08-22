<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$userName = $_SESSION['user_name'] ?? '';

if ($isAdmin) {
    // Nội dung cho admin - quản lý người dùng
    $content = '
    <div class="user-container">
        <h2>Quản lý người dùng</h2>
        <p>Chức năng quản lý người dùng đang được phát triển.</p>
        <div class="user-actions">
            <button class="btn-primary" onclick="addUser()">Thêm người dùng</button>
            <button class="btn-secondary" onclick="editUser()">Chỉnh sửa</button>
            <button class="btn-danger" onclick="deleteUser()">Xóa người dùng</button>
        </div>
        <div class="user-list">
            <h3>Danh sách người dùng</h3>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Số điện thoại</th>
                        <th>Quyền</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                    <!-- User data will be loaded here -->
                </tbody>
            </table>
        </div>
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">Đăng xuất</a>
        </div>
    </div>';
} else {
    // Nội dung cho người dùng thường - thông tin cá nhân
    $content = '
    <div class="user-container">
        <h2>Thông tin cá nhân</h2>
        <div class="user-profile">
            <div class="profile-info">
                <h3>Thông tin cơ bản</h3>
                <p><strong>Tên:</strong> ' . htmlspecialchars($userName) . '</p>
                <p><strong>ID:</strong> ' . $_SESSION['user_id'] . '</p>
                <p><strong>Quyền:</strong> Người dùng</p>
            </div>
            <div class="profile-actions">
                <button class="btn-primary" onclick="editProfile()">Chỉnh sửa thông tin</button>
                <button class="btn-secondary" onclick="changePassword()">Đổi mật khẩu</button>
            </div>
        </div>
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">Đăng xuất</a>
        </div>
    </div>';
}

echo json_encode([
    'success' => true,
    'content' => $content,
    'is_admin' => $isAdmin
]);
?>
