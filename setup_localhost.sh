#!/bin/bash

echo "=== Cài đặt dự án TAY LÁI BỤI SÓC SƠN trên Localhost ==="
echo ""

# Kiểm tra XAMPP
XAMPP_PATH="/Applications/XAMPP"
if [ ! -d "$XAMPP_PATH" ]; then
    echo "❌ XAMPP không được tìm thấy tại $XAMPP_PATH"
    echo "Vui lòng cài đặt XAMPP trước: https://www.apachefriends.org/download.html"
    exit 1
fi

echo "✅ XAMPP đã được tìm thấy"

# Tạo thư mục dự án trong htdocs
PROJECT_DIR="$XAMPP_PATH/htdocs/taylaibui"
if [ -d "$PROJECT_DIR" ]; then
    echo "⚠️  Thư mục dự án đã tồn tại. Xóa thư mục cũ..."
    rm -rf "$PROJECT_DIR"
fi

echo "📁 Tạo thư mục dự án..."
mkdir -p "$PROJECT_DIR"

# Copy tất cả file dự án
echo "📋 Copy file dự án..."
cp -r . "$PROJECT_DIR/"

# Backup file db.php gốc
if [ -f "$PROJECT_DIR/db.php" ]; then
    echo "💾 Backup file db.php gốc..."
    mv "$PROJECT_DIR/db.php" "$PROJECT_DIR/db_production.php"
fi

# Copy file db_local.php thành db.php
if [ -f "$PROJECT_DIR/db_local.php" ]; then
    echo "🔧 Cập nhật cấu hình database cho localhost..."
    cp "$PROJECT_DIR/db_local.php" "$PROJECT_DIR/db.php"
fi

# Thiết lập quyền
echo "🔐 Thiết lập quyền file..."
chmod -R 755 "$PROJECT_DIR"

echo ""
echo "=== Cài đặt hoàn tất! ==="
echo ""
echo "📋 Các bước tiếp theo:"
echo "1. Khởi động XAMPP Control Panel"
echo "2. Start Apache và MySQL"
echo "3. Mở trình duyệt và truy cập: http://localhost/phpmyadmin"
echo "4. Tạo database 'taylaibui_db'"
echo "5. Import file database_setup.sql"
echo "6. Truy cập dự án: http://localhost/taylaibui/"
echo ""
echo "📚 Xem file SETUP_LOCALHOST.md để biết thêm chi tiết"
echo ""
echo "🎉 Chúc bạn thành công!"
