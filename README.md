# BE Mobile - Laravel JWT Authentication API

Backend API cho ứng dụng mobile sử dụng Laravel 11 và JWT Authentication.

## 🚀 Tính năng

- ✅ JWT Authentication (Access Token + Refresh Token)
- ✅ **Role-based Access Control (Admin, Partner, User)**
- ✅ User Registration & Login
- ✅ Profile Management
- ✅ Change Password
- ✅ Delete Account
- ✅ Token Refresh
- ✅ Product Management (Upload, List, Update, Delete)
- ✅ Image Upload & Storage
- ✅ Protected Routes với Middleware
- ✅ NFC Tag Integration (Public API cho scan NFC)
- ✅ Owner Management (Đổi chủ sở hữu qua NFC)
- ✅ **User Creation by Admin/Partner with Role Restrictions**

## 📋 Yêu cầu

- PHP >= 8.2
- Composer
- MySQL/PostgreSQL
- Firebase JWT Library

## 🔧 Cài đặt

```bash
# Clone repository
git clone https://github.com/npbtruong/be_mobile.git
cd be_mobile/laravel

# Cài đặt dependencies
composer install

# Copy file .env
cp .env.example .env

# Generate application key
php artisan key:generate

# Tạo JWT secret key
php -r "echo base64_encode(random_bytes(32));"

# Chạy migration
php artisan migrate

# Tạo symbolic link cho storage (để truy cập file uploads)
php artisan storage:link

# Chạy server
php artisan serve
```

## 🔑 Cấu hình JWT

Trong file `.env`, thêm:
```
JWT_SECRET=your-secret-key-here
JWT_ALGO=HS256
JWT_TTL=3600
JWT_REFRESH_TTL=604800
```

## 📚 API Endpoints

### 🔐 Hệ thống Phân quyền (Role-based Access Control)

Ứng dụng sử dụng 3 loại role:

| Role | Quyền tạo User | Quyền tạo Partner | Quyền tạo Admin |
|------|---------------|-------------------|-----------------|
| **Admin** | ✅ Có | ✅ Có | ✅ Có |
| **Partner** | ✅ Có | ❌ Không | ❌ Không |
| **User** | ❌ Không | ❌ Không | ❌ Không |

**Lưu ý:**
- Đăng ký công khai (`POST /api/auth/register`) chỉ tạo user với role `user`
- Admin và Partner phải sử dụng endpoint `POST /api/auth/create-user` để tạo user mới

---

### Public Routes (Không cần token)

#### Register
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "Test User",
    "email": "test@example.com",
    "password": "123456",
    "password_confirmation": "123456"
}

Response:
{
    "message": "Đăng ký thành công",
    "user": {
        "id": 1,
        "name": "Test User",
        "email": "test@example.com",
        "role": "user"  // Luôn là 'user' khi đăng ký công khai
    },
    "access_token": "...",
    "refresh_token": "...",
    "token_type": "Bearer"
}

Note: Đăng ký công khai chỉ tạo user với role 'user'
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "test@example.com",
    "password": "123456"
}
```

#### Refresh Token
```http
POST /api/auth/refresh
Content-Type: application/json

{
    "refresh_token": "your_refresh_token"
}
```

### Protected Routes (Cần Bearer Token)

#### Get Profile
```http
GET /api/auth/profile
Authorization: Bearer {access_token}
```

#### Update Profile
```http
PUT /api/auth/profile
Authorization: Bearer {access_token}
Content-Type: application/json

{
    "name": "Updated Name",
    "email": "newemail@example.com"
}
```

#### Change Password
```http
PUT /api/auth/change-password
Authorization: Bearer {access_token}
Content-Type: application/json

{
    "current_password": "123456",
    "new_password": "newpass123",
    "new_password_confirmation": "newpass123"
}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {access_token}
```

#### Delete Account
```http
DELETE /api/auth/delete-account
Authorization: Bearer {access_token}
Content-Type: application/json

{
    "password": "123456"
}
```

#### Create User (Admin/Partner Only) 🆕
```http
POST /api/auth/create-user
Authorization: Bearer {access_token}
Content-Type: application/json

{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "user"  // Có thể là: "user", "partner", "admin"
}

Response Success:
{
    "message": "Tạo tài khoản thành công",
    "user": {
        "id": 5,
        "name": "New User",
        "email": "newuser@example.com",
        "role": "user"
    }
}

Response Error (Không đủ quyền):
{
    "message": "Bạn không có quyền tạo tài khoản Partner"
}

Phân quyền:
- Admin: Có thể tạo user với role: "user", "partner", "admin"
- Partner: Chỉ có thể tạo user với role: "user"
- User: Không thể sử dụng endpoint này (403 Forbidden)
```

### Product Management Routes

#### Upload Product (with Image)
```http
POST /api/products
Authorization: Bearer {access_token}
Content-Type: multipart/form-data

Form Data:
- image: [file] (required, jpeg/jpg/png/gif, max 5MB)
- describe: Mô tả sản phẩm (optional)
- owner_name: Tên chủ sở hữu (optional)
- owner_email: Email chủ sở hữu (optional)

Note: tag_id sẽ được tự động generate dạng NFC-XXXXXX (unique, không thể đoán được)
```

#### Get All Products
```http
GET /api/products
Authorization: Bearer {access_token}

Query Parameters (optional):
- my_products: true (lấy sản phẩm của user hiện tại)
- user_id: 1 (lấy sản phẩm của user cụ thể)
- tag_id: ABC (tìm kiếm theo tag_id)
```

#### Get Product Detail
```http
GET /api/products/{id}
Authorization: Bearer {access_token}
```

#### Update Product
```http
POST /api/products/{id}
Authorization: Bearer {access_token}
Content-Type: multipart/form-data

Form Data (tất cả đều optional):
- image: [new_file]
- describe: Mô tả mới
- owner_name: Tên chủ sở hữu mới
- owner_email: Email chủ sở hữu mới

Note: 
- Chỉ owner của sản phẩm mới có quyền update
- tag_id KHÔNG THỂ sửa (đã cố định khi tạo)
```

#### Delete Product
```http
DELETE /api/products/{id}
Authorization: Bearer {access_token}

Note: Chỉ owner của sản phẩm mới có quyền xóa. Ảnh sẽ tự động bị xóa khỏi storage.
```

#### Get User Statistics
```http
GET /api/products/statistics
Authorization: Bearer {access_token}
```

### NFC Routes (Public - Không cần token)

#### Get Product by Tag ID (NFC Scan)
```http
GET /api/nfc/{tag_id}

Example: GET /api/nfc/ABC123

Response:
{
    "product": {
        "tag_id": "ABC123",
        "image_url": "/storage/products/image.jpg",
        "describe": "Mô tả sản phẩm",
        "owner_name": "Nguyễn Văn A",
        "owner_email": "owner@email.com",
        "created_at": "2024-12-21T10:00:00.000000Z",
        "uploaded_by": {
            "name": "Admin User",
            "email": "admin@email.com"
        }
    }
}
```

#### Update Owner Information (NFC Scan)
```http
PUT /api/nfc/{tag_id}/owner
Content-Type: application/json

{
    "owner_name": "Trần Thị B",
    "owner_email": "tranb@email.com"
}

Response:
{
    "message": "Cập nhật thông tin chủ sở hữu thành công",
    "product": {
        "tag_id": "ABC123",
        "owner_name": "Trần Thị B",
        "owner_email": "tranb@email.com"
    }
}

Note: Chỉ cho phép cập nhật owner_name và owner_email.
Không thể sửa: tag_id, image_url, describe, uploaded_by
```

## 📱 Hệ thống NFC Tag

### Luồng hoạt động:

1. **Admin tạo sản phẩm:**
   - Upload ảnh áo qua API `POST /api/products`
   - Nhận về `nfc_url`: `http://yourdomain.com/nfc/ABC123`
   - Ghi URL này vào chip NFC

2. **Người mua scan NFC:**
   - Điện thoại mở URL: `http://yourdomain.com/nfc/ABC123`
   - Website gọi API `GET /api/nfc/ABC123` để hiển thị thông tin áo
   
3. **Đổi chủ sở hữu:**
   - Người đang giữ áo nhập tên và email mới
   - Gọi API `PUT /api/nfc/ABC123/owner` để cập nhật

### Response khi upload sản phẩm:
```json
{
    "message": "Sản phẩm đã được tạo thành công",
    "product": {
        "id": 1,
        "tag_id": "NFC-A7B2C9",
        "image_url": "/storage/products/image.jpg",
        ...
    },
    "nfc_url": "http://localhost:8000/nfc/NFC-A7B2C9",
    "nfc_instructions": "Ghi URL này vào thẻ NFC để khách hàng có thể scan"
}
```

**Lưu ý:** 
- `tag_id` được tự động generate dạng `NFC-XXXXXX` (6 ký tự ngẫu nhiên)
- Unique và không thể đoán được → Bảo mật cao hơn
- Không thể sửa sau khi tạo

## 🏗️ Cấu trúc Project

```
laravel/
├── app/
│   ├── Helpers/
│   │   └── JWTHelper.php              # JWT utility functions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php     # Authentication & User Management
│   │   │   └── ProductController.php  # Product management controller
│   │   └── Middleware/
│   │       ├── JWTAuthMiddleware.php  # JWT authentication
│   │       └── CheckRole.php          # Role-based access control
│   └── Models/
│       ├── User.php                   # User model với role methods
│       └── Product.php
├── bootstrap/
│   └── app.php                        # Middleware registration
├── database/
│   └── migrations/
│       ├── create_users_table.php
│       ├── create_products_table.php
│       └── add_role_to_users_table.php # Migration thêm role
├── public/
│   └── storage/                       # Symbolic link to storage/app/public
├── routes/
│   └── api.php                        # API routes với role middleware
├── storage/
│   └── app/
│       └── public/
│           └── products/              # Product images
└── .env                               # Environment variables
```

## 🔒 Security

- Passwords được hash với bcrypt
- JWT tokens có thời gian hết hạn
- Refresh tokens riêng biệt với access tokens
- Middleware bảo vệ protected routes
- **Role-based Access Control (RBAC) với 3 levels: Admin, Partner, User**
- **Middleware kiểm tra quyền truy cập theo role**
- **Phân quyền tạo user theo role hierarchy**

## 🎯 Use Cases

### 1. Đăng ký User thường (Public)
```bash
# Bất kỳ ai cũng có thể đăng ký user thường
POST /api/auth/register
→ Tạo user với role: "user"
```

### 2. Admin tạo Partner
```bash
# Chỉ Admin mới có quyền tạo Partner
POST /api/auth/create-user
Authorization: Bearer {admin_token}
{
  "role": "partner",
  ...
}
→ Success: Tạo partner mới
```

### 3. Partner tạo User
```bash
# Partner có thể tạo User thường
POST /api/auth/create-user
Authorization: Bearer {partner_token}
{
  "role": "user",
  ...
}
→ Success: Tạo user mới

# Partner KHÔNG THỂ tạo Partner
{
  "role": "partner",
  ...
}
→ Error 403: "Bạn không có quyền tạo tài khoản Partner"
```

### 4. User thường không thể tạo user
```bash
# User thường không có quyền truy cập endpoint này
POST /api/auth/create-user
Authorization: Bearer {user_token}
→ Error 403: "Bạn không có quyền truy cập chức năng này"
```

## 📝 License

MIT License

## 👤 Author

npbtruong - [GitHub](https://github.com/npbtruong)
