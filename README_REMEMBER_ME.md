# Tính năng Ghi nhớ đăng nhập - TAY LÁI BỤI SÓC SƠN

## Tổng quan
Hệ thống TAY LÁI BỤI SÓC SƠN đã được cải thiện với tính năng **"Ghi nhớ đăng nhập trong 7 ngày"**. Tính năng này cho phép người dùng không phải đăng nhập lại mỗi khi mở trình duyệt trong vòng 7 ngày.

## Cách hoạt động

### 1. Khi đăng nhập
- **Có chọn checkbox "Ghi nhớ đăng nhập"**: Hệ thống sẽ tạo cookie lưu thông tin đăng nhập trong 7 ngày
- **Không chọn checkbox**: Chỉ sử dụng session thông thường (hết hạn khi đóng trình duyệt)

### 2. Khi truy cập lại
- Hệ thống kiểm tra cookie `session_expires` để xác định thời gian hết hạn
- Nếu cookie còn hạn: Tự động đăng nhập và khôi phục session
- Nếu cookie hết hạn: Xóa cookie và yêu cầu đăng nhập lại

### 3. Khi đăng xuất
- Tất cả cookie và session đều bị xóa
- Người dùng phải đăng nhập lại

## Các file đã được cập nhật

### 1. `login.php`
- ✅ Thêm checkbox "Ghi nhớ đăng nhập trong 7 ngày"
- ✅ Logic xử lý cookie chỉ khi người dùng chọn ghi nhớ
- ✅ Tạo cookie với thời gian hết hạn 7 ngày

### 2. `api_login.php`
- ✅ Xử lý checkbox từ modal đăng nhập
- ✅ Tạo cookie `user_id`, `user_name`, `is_admin`, `session_expires`
- ✅ Thời gian hết hạn: 7 ngày

### 3. `index.php`
- ✅ Logic kiểm tra cookie và khôi phục session
- ✅ Tự động đăng nhập nếu cookie còn hạn
- ✅ Xóa cookie hết hạn

### 4. `logout.php`
- ✅ Xóa tất cả cookie liên quan đến ghi nhớ đăng nhập
- ✅ Đảm bảo đăng xuất hoàn toàn

### 5. `config.php`
- ✅ Cấu hình session bảo mật
- ✅ Cookie HTTP-only và SameSite

## Cách test tính năng

### Bước 1: Đăng nhập với ghi nhớ
1. Truy cập trang đăng nhập
2. Nhập thông tin đăng nhập
3. **Chọn checkbox "Ghi nhớ đăng nhập trong 1 tuần"**
4. Nhấn "Đăng nhập"

### Bước 2: Kiểm tra cookie
1. Mở Developer Tools (F12)
2. Vào tab Application/Storage > Cookies
3. Kiểm tra các cookie:
   - `user_id`
   - `user_name` 
   - `is_admin`
   - `session_expires`

### Bước 3: Test tự động đăng nhập
1. Đóng trình duyệt hoàn toàn
2. Mở lại trình duyệt
3. Truy cập lại trang web
4. Kiểm tra xem có tự động đăng nhập không

### Bước 4: Test hết hạn
1. Đợi 7 ngày hoặc sửa thời gian hết hạn trong cookie
2. Kiểm tra xem có yêu cầu đăng nhập lại không

## Thông tin kỹ thuật

### Cookie được tạo
```php
// Khi chọn ghi nhớ đăng nhập
$expires = time() + (7 * 24 * 60 * 60); // 7 days

setcookie('user_id', $uid, $expires, '/');
setcookie('user_name', $uname, $expires, '/');
setcookie('is_admin', $isAdmin, $expires, '/');
setcookie('session_expires', $expires, $expires, '/');
```

### Bảo mật
- Cookie được đặt với flag `httponly` (không thể truy cập qua JavaScript)
- Cookie được đặt với flag `SameSite=Lax` (bảo vệ CSRF)
- Thời gian hết hạn được lưu trong cookie `session_expires`
- Tự động xóa cookie hết hạn

### Xử lý lỗi
- Kiểm tra tính hợp lệ của cookie trước khi sử dụng
- Tự động xóa cookie hết hạn
- Fallback về session thông thường nếu có vấn đề với cookie

## Troubleshooting

### Vấn đề: Không tự động đăng nhập
**Nguyên nhân có thể:**
1. Cookie bị xóa bởi trình duyệt
2. Cookie hết hạn
3. Vấn đề với domain/path của cookie

**Giải pháp:**
1. Kiểm tra cookie trong Developer Tools
2. Kiểm tra thời gian hết hạn
3. Đăng nhập lại với checkbox được chọn

### Vấn đề: Cookie không được tạo
**Nguyên nhân có thể:**
1. Checkbox không được chọn
2. Lỗi trong quá trình tạo cookie
3. Vấn đề với quyền ghi file

**Giải pháp:**
1. Đảm bảo checkbox được chọn
2. Kiểm tra error log
3. Kiểm tra quyền thư mục

## Kết luận
Tính năng "Ghi nhớ đăng nhập trong 7 ngày" đã được triển khai hoàn chỉnh và bảo mật. Người dùng có thể:
- Chọn có muốn ghi nhớ đăng nhập hay không
- Tự động đăng nhập trong 7 ngày
- Đăng xuất an toàn khi cần

Hệ thống đảm bảo bảo mật thông qua việc sử dụng cookie HTTP-only và kiểm tra thời gian hết hạn.
