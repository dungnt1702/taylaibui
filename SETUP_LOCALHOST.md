# Hướng dẫn cài đặt dự án TAY LÁI BỤI SÓC SƠN trên Localhost

## Yêu cầu hệ thống
- macOS (đã có sẵn)
- XAMPP hoặc MAMP
- Trình duyệt web (Chrome, Firefox, Safari)

## Bước 1: Cài đặt XAMPP
1. Tải XAMPP từ: https://www.apachefriends.org/download.html
2. Chọn phiên bản cho macOS
3. Cài đặt theo hướng dẫn mặc định

## Bước 2: Khởi động XAMPP
1. Mở XAMPP Control Panel
2. Nhấn "Start" cho Apache và MySQL
3. Đảm bảo cả hai service đều có status "Running"

## Bước 3: Thiết lập cơ sở dữ liệu
1. Mở trình duyệt và truy cập: `http://localhost/phpmyadmin`
2. Tạo database mới:
   - Nhấn "New" (Mới)
   - Đặt tên: `taylaibui_db`
   - Chọn collation: `utf8mb4_unicode_ci`
   - Nhấn "Create" (Tạo)

3. Import cấu trúc database:
   - Chọn database `taylaibui_db`
   - Nhấn tab "Import"
   - Chọn file `database_setup.sql`
   - Nhấn "Go" để import

## Bước 4: Cập nhật cấu hình
1. Đổi tên file `db.php` thành `db_production.php` (backup)
2. Đổi tên file `db_local.php` thành `db.php`

## Bước 5: Truy cập dự án
1. Copy toàn bộ thư mục dự án vào: `/Applications/XAMPP/htdocs/taylaibui/`
2. Mở trình duyệt và truy cập: `http://localhost/taylaibui/`

## Thông tin đăng nhập mặc định
- **Username:** `admin`
- **Password:** `password`
- **Username:** `user1`  
- **Password:** `password`

## Cấu trúc thư mục sau khi cài đặt
```
/Applications/XAMPP/htdocs/taylaibui/
├── index.php
├── db.php (đã cập nhật cho localhost)
├── login.php
├── functions.php
├── style.css
├── script.js
└── ... (các file khác)
```

## Xử lý lỗi thường gặp

### Lỗi kết nối database
- Kiểm tra MySQL đã khởi động chưa
- Kiểm tra tên database có đúng không
- Kiểm tra username/password trong file db.php

### Lỗi 404 Not Found
- Kiểm tra thư mục dự án có đúng vị trí không
- Kiểm tra Apache đã khởi động chưa

### Lỗi permission
- Đảm bảo thư mục dự án có quyền đọc/ghi

## Ghi chú
- Dự án sử dụng PHP 7.4+ và MySQL 5.7+
- Hỗ trợ tiếng Việt với encoding UTF-8
- Session được lưu trong thư mục mặc định của XAMPP
