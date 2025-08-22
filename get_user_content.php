<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Lấy thông tin user từ database thay vì session
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Kết nối database thất bại']);
    exit;
}

$mysqli->set_charset("utf8");

$currentUserId = $_SESSION['user_id'];
$query = "SELECT name, phone, is_admin FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin người dùng']);
    exit;
}

$userData = $result->fetch_assoc();
$userName = $userData['name'];
$userPhone = $userData['phone'];
$userIsAdmin = (bool)$userData['is_admin'];

$stmt->close();
$mysqli->close();

if ($isAdmin) {
    // Nội dung cho admin - quản lý người dùng
    $content = '
    <div class="user-container">
        <div class="user-actions">
            <button class="btn-primary" onclick="showAddUserModal()">➕ Thêm người dùng</button>
            <a href="logout.php" class="logout-btn">🚪 Đăng xuất</a>
        </div>
        <div class="user-list">
            <h3>Danh sách người dùng</h3>
            <div class="user-table-container">
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
        </div>
    </div>
    
    <!-- Modal thêm người dùng mới -->
    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddUserModal()">&times;</span>
            <h2>➕ Thêm người dùng mới</h2>
            <form id="add-user-form" method="POST" onsubmit="return false;">
                <div class="form-group">
                    <label for="new-user-name">Tên người dùng:</label>
                    <input type="text" id="new-user-name" name="name" required placeholder="Nhập tên người dùng">
                </div>
                <div class="form-group">
                    <label for="new-user-phone">Số điện thoại:</label>
                    <input type="tel" id="new-user-phone" name="phone" required placeholder="Nhập số điện thoại">
                </div>
                <div class="form-group">
                    <label for="new-user-password">Mật khẩu:</label>
                    <input type="password" id="new-user-password" name="password" required placeholder="Nhập mật khẩu">
                </div>
                <div class="form-group">
                    <label for="new-user-role">Quyền:</label>
                    <select id="new-user-role" name="role" required>
                        <option value="">Chọn quyền</option>
                        <option value="0">Người dùng</option>
                        <option value="1">Quản trị viên</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Lưu người dùng</button>
                    <button type="button" onclick="closeAddUserModal()" class="btn-secondary">Hủy</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal chỉnh sửa người dùng -->
    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditUserModal()">&times;</span>
            <h2>✏️ Chỉnh sửa người dùng</h2>
            <form id="edit-user-form" method="POST" onsubmit="return false;">
                <input type="hidden" id="edit-user-id" name="user_id">
                <div class="form-group">
                    <label for="edit-user-name">Tên người dùng:</label>
                    <input type="text" id="edit-user-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit-user-phone">Số điện thoại:</label>
                    <input type="tel" id="edit-user-phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="edit-user-password">Mật khẩu mới (để trống nếu không đổi):</label>
                    <input type="password" id="edit-user-password" name="password" placeholder="Nhập mật khẩu mới">
                </div>
                <div class="form-group">
                    <label for="edit-user-role">Quyền:</label>
                    <select id="edit-user-role" name="role" required>
                        <option value="0">Người dùng</option>
                        <option value="1">Quản trị viên</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Cập nhật</button>
                    <button type="button" onclick="closeEditUserModal()" class="btn-secondary">Hủy</button>
                </div>
            </form>
        </div>
    </div>';
} else {
    // Nội dung cho người dùng thường - thông tin cá nhân
    $content = '
    <div class="user-container">
        <div class="user-actions">
            <a href="logout.php" class="logout-btn">🚪 Đăng xuất</a>
        </div>
        <div class="user-profile">
            <div class="profile-info">
                <h3>Thông tin cơ bản</h3>
                <p><strong>Tên:</strong> ' . htmlspecialchars($userName) . '</p>
                <p><strong>Số điện thoại:</strong> ' . htmlspecialchars($userPhone) . '</p>
                <p><strong>ID:</strong> ' . $currentUserId . '</p>
                <p><strong>Quyền:</strong> ' . ($userIsAdmin ? 'Quản trị viên' : 'Người dùng') . '</p>
            </div>
            <div class="profile-actions">
                <button class="btn-primary" onclick="showEditProfileModal()">✏️ Chỉnh sửa thông tin</button>
            </div>
        </div>
    </div>
    
    <!-- Modal chỉnh sửa thông tin cá nhân -->
    <div id="edit-profile-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditProfileModal()">&times;</span>
            <h2>✏️ Chỉnh sửa thông tin cá nhân</h2>
            <form id="edit-profile-form" method="POST" onsubmit="return false;">
                <input type="hidden" id="edit-profile-user-id" name="user_id" value="' . $currentUserId . '">
                <div class="form-group">
                    <label for="edit-profile-name">Tên người dùng:</label>
                    <input type="text" id="edit-profile-name" name="name" value="' . htmlspecialchars($userName) . '" required>
                </div>
                <div class="form-group">
                    <label for="edit-profile-phone">Số điện thoại:</label>
                    <input type="tel" id="edit-profile-phone" name="phone" value="' . htmlspecialchars($userPhone) . '" disabled>
                    <small style="color: #666;">Số điện thoại không thể thay đổi</small>
                </div>
                <div class="form-group">
                    <label for="edit-profile-password">Mật khẩu mới (để trống nếu không đổi):</label>
                    <input type="password" id="edit-profile-password" name="password" placeholder="Nhập mật khẩu mới">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Cập nhật thông tin</button>
                    <button type="button" onclick="closeEditProfileModal()" class="btn-secondary">Hủy</button>
                </div>
            </form>
        </div>
    </div>';
}

echo json_encode([
    'success' => true,
    'content' => $content,
    'is_admin' => $isAdmin,
    'current_user_id' => $_SESSION['user_id'] ?? null
]);
?>
