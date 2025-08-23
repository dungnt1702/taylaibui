-- Cập nhật cấu trúc bảng users để thêm các cột timestamp
-- Chạy file này để cập nhật database hiện có

USE tay99672_qlss;

-- Thêm cột created_at nếu chưa có
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Thêm cột updated_at nếu chưa có
ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Cập nhật giá trị created_at cho các user hiện có (nếu cần)
UPDATE users SET created_at = NOW() WHERE created_at IS NULL;

-- Cập nhật giá trị updated_at cho các user hiện có (nếu cần)
UPDATE users SET updated_at = NOW() WHERE updated_at IS NULL;

-- Kiểm tra cấu trúc bảng sau khi cập nhật
DESCRIBE users;

-- Hiển thị một số user để kiểm tra
SELECT id, name, phone, is_admin, is_active, created_at, updated_at FROM users LIMIT 5;
