# 🚀 Hướng dẫn Deployment và Cấu hình Môi trường

## 📋 Tổng quan

Hệ thống này được thiết kế để **tự động nhận diện môi trường** và sử dụng file database phù hợp:

- **Local Development** → `db.php`
- **Production Server** → `db_production.php`

## 🏗️ Cấu trúc Files

```
taylaibui/
├── config.php              # File cấu hình chính (tự động nhận diện môi trường)
├── db.php                  # Database config cho local development
├── db_production.php       # Database config cho production server
├── env.local               # Marker cho môi trường local
├── .env.production         # Marker cho môi trường production
├── deploy.sh               # Script deploy tự động
├── .gitignore              # Quản lý file bảo mật
└── README_DEPLOYMENT.md    # File này
```

## 🔧 Cách hoạt động

### 1. **Tự động nhận diện môi trường:**

`config.php` sử dụng **6 phương pháp** để nhận diện:

```php
// Method 1: Server hostname/IP
$server_name = $_SERVER['SERVER_NAME'] ?? '';

// Method 2: Localhost detection
$is_localhost = in_array($server_name, ['localhost', '127.0.0.1', '::1']);

// Method 3: Development domains
$is_dev = strpos($server_name, '.local') !== false ||
          strpos($server_name, '.test') !== false ||
          strpos($server_name, '.dev') !== false;

// Method 4: Production domains
$production_domains = ['taylaibui.com', 'www.taylaibui.com'];

// Method 5: Environment variables
$env_var = getenv('APP_ENV') ?: getenv('ENVIRONMENT');

// Method 6: Environment files
if (file_exists('.env.production')) return 'production';
if (file_exists('.env.local')) return 'development';
```

### 2. **Tự động include database:**

```php
function includeDatabase() {
    $environment = detectEnvironment();
    
    switch ($environment) {
        case 'production':
            require_once 'db_production.php';  // Production DB
            break;
        case 'development':
        default:
            require_once 'db.php';             // Local DB
            break;
    }
}
```

## 🚀 Cách sử dụng

### **Bước 1: Cấu hình Production Database**

1. **Sửa file `db_production.php`:**
```php
$db_host = 'your-production-host.com';
$db_user = 'your_production_username';
$db_pass = 'your_production_password';
$db_name = 'your_production_database';
```

2. **Sửa domain trong `config.php`:**
```php
$production_domains = [
    'yourdomain.com',           // Thay bằng domain thật
    'www.yourdomain.com',       // Thay bằng domain thật
];
```

### **Bước 2: Deploy lên server**

#### **Cách 1: Sử dụng script deploy (Khuyến nghị)**

1. **Sửa thông tin server trong `deploy.sh`:**
```bash
REMOTE_HOST="your-server-ip-or-domain"
REMOTE_USER="your-username"
REMOTE_DIR="/var/www/html/qlss"
```

2. **Chạy script deploy:**
```bash
chmod +x deploy.sh
./deploy.sh
```

3. **Chọn loại deployment:**
   - **1) Production** → Sử dụng `db_production.php`
   - **2) Development** → Sử dụng `db.php`

#### **Cách 2: Upload thủ công**

1. **Upload tất cả files** lên server
2. **Tạo file `.env.production`** trên server:
```bash
echo "ENVIRONMENT=production" > .env.production
echo "APP_ENV=production" >> .env.production
```

### **Bước 3: Kiểm tra**

1. **Kiểm tra môi trường:**
```php
// Thêm vào file PHP để debug
echo "Environment: " . CURRENT_ENVIRONMENT;
echo "Is Production: " . (IS_PRODUCTION ? 'Yes' : 'No');
echo "Database: " . (isset($mysqli) ? 'Connected' : 'Not connected');
```

2. **Kiểm tra logs:**
```bash
# Nếu DEBUG_MODE = true
tail -f logs/error.log
```

## 🔒 Bảo mật

### **1. File không được commit lên git:**
- ✅ `db_production.php` (chứa thông tin nhạy cảm)
- ✅ `.env.production`
- ✅ `env.local`

### **2. File được commit lên git:**
- ✅ `config.php` (logic nhận diện môi trường)
- ✅ `db.php` (database local)
- ✅ `deploy.sh` (script deploy)

### **3. Cách bảo vệ thông tin nhạy cảm:**
```bash
# Tạo file db_production.php từ template
cp db_production.php.template db_production.php

# Sửa thông tin database
nano db_production.php

# Đảm bảo file không bị commit
git status  # Không thấy db_production.php
```

## 🐛 Troubleshooting

### **Vấn đề 1: Không nhận diện được môi trường**

**Nguyên nhân:**
- Server name không khớp với cấu hình
- File marker không tồn tại

**Giải pháp:**
```php
// Thêm vào config.php để debug
define('DEBUG_MODE', true);

// Kiểm tra logs
tail -f logs/error.log
```

### **Vấn đề 2: Database connection failed**

**Nguyên nhân:**
- Thông tin database sai
- Firewall chặn kết nối
- Database server không hoạt động

**Giải pháp:**
```bash
# Test kết nối database
mysql -h your-host -u your-user -p your-database

# Kiểm tra firewall
sudo ufw status

# Kiểm tra database service
sudo systemctl status mysql
```

### **Vấn đề 3: Permission denied**

**Nguyên nhân:**
- User không có quyền ghi
- SELinux blocking

**Giải pháp:**
```bash
# Set permissions
sudo chown -R www-data:www-data /var/www/html/qlss
sudo chmod -R 755 /var/www/html/qlss

# Check SELinux
sestatus
```

## 📱 Test trên Mobile

### **1. Test responsive design:**
- Mở Developer Tools (F12)
- Chọn Device Toolbar
- Test các kích thước màn hình khác nhau

### **2. Test database switching:**
```php
// Thêm vào file để test
echo "<div style='background: yellow; padding: 10px;'>";
echo "Current Environment: " . CURRENT_ENVIRONMENT . "<br>";
echo "Database File: " . (IS_PRODUCTION ? 'db_production.php' : 'db.php') . "<br>";
echo "Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown');
echo "</div>";
```

## 🔄 Workflow Development

### **1. Local Development:**
```bash
# Chạy local
php -S localhost:8000

# Sử dụng db.php
# Environment: development
```

### **2. Deploy to Production:**
```bash
# Chạy script deploy
./deploy.sh

# Chọn option 1 (Production)
# Sử dụng db_production.php
# Environment: production
```

### **3. Rollback if needed:**
```bash
# SSH vào server
ssh user@server

# Restore từ backup
sudo cp -r /var/www/html/qlss_backup/qlss_20250120_143022/* /var/www/html/qlss/
```

## 📊 Monitoring

### **1. Environment Detection Logs:**
```php
// Trong config.php
if (DEBUG_MODE) {
    error_log("=== Environment Info ===");
    error_log("Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown'));
    error_log("Environment: " . $environment);
    error_log("Database: " . (isset($mysqli) ? 'Connected' : 'Not connected'));
}
```

### **2. Database Connection Status:**
```php
// Kiểm tra kết nối database
if ($mysqli->ping()) {
    echo "Database connection: OK";
} else {
    echo "Database connection: FAILED";
}
```

## 🎯 Best Practices

### **1. Security:**
- ✅ **Không commit** thông tin database production
- ✅ **Sử dụng HTTPS** trên production
- ✅ **Backup thường xuyên** trước khi deploy

### **2. Performance:**
- ✅ **Enable caching** trên production
- ✅ **Optimize images** và assets
- ✅ **Minify CSS/JS** trên production

### **3. Maintenance:**
- ✅ **Test locally** trước khi deploy
- ✅ **Monitor logs** sau khi deploy
- ✅ **Keep backups** của mọi version

## 🚀 Kết luận

Với hệ thống này, bạn có thể:

1. **Develop locally** với `db.php`
2. **Deploy to production** với `db_production.php`
3. **Tự động nhận diện** môi trường
4. **Bảo mật** thông tin nhạy cảm
5. **Deploy dễ dàng** với script tự động

**Hệ thống sẽ tự động:**
- ✅ **Nhận diện** môi trường local/production
- ✅ **Include** database config phù hợp
- ✅ **Set** error reporting phù hợp
- ✅ **Log** thông tin môi trường

**Không cần thay đổi code** khi chuyển từ local sang production! 🎉
