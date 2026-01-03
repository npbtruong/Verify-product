# BE Mobile - Laravel JWT Authentication API

Backend API cho ứng dụng mobile sử dụng Laravel 11 và JWT Authentication.

## 🚀 Tính năng

- ✅ JWT Authentication (Access Token + Refresh Token)
- ✅ Role-based Access Control (Admin, Partner, User)
- ✅ Partner Management System với auto-inheritance
- ✅ User-Partner Relationship & Created-By Tracking
- ✅ Product Management với NFC Tag Integration
- ✅ Image Upload & Storage
- ✅ Owner Update System (OTP via Email)

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
    // User role "user" KHÔNG được đổi partner_id
    // Admin/Partner có thể đổi partner_id của chính họ
    // Khi Admin/Partner đổi partner_id → tất cả user do họ tạo cũng đổi theo
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
    "role": "user"  // "user", "partner", "admin"
}

Logic partner_id:
- Tạo USER: TỰ ĐỘNG kế thừa partner_id từ người tạo
- Tạo PARTNER/ADMIN: Cho phép manual set partner_id, hoặc null

Phân quyền:
- Admin: Tạo được user, partner, admin
- Partner: Chỉ tạo được user
- User: Không có quyền
```

### Partner Management Routes 🆕

```http
# Danh sách partners
GET /api/partners
Authorization: Bearer {access_token}

# Chi tiết partner
GET /api/partners/{id}
Authorization: Bearer {access_token}

# Tạo partner (Admin/Partner)
POST /api/partners
Authorization: Bearer {access_token}
{
    "name": "Partner Name",
    "domain": "domain.com",  // required, unique
    "brand": "Brand Name"     // optional
}

# Cập nhật partner (Admin/Partner)
PUT /api/partners/{id}
Authorization: Bearer {access_token}

# Xóa partner (Admin only)
DELETE /api/partners/{id}
Authorization: Bearer {access_token}
```

### Product Management Routes

```http
# Upload sản phẩm
POST /api/products
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
Form: image, describe, owner_name, owner_email

# Danh sách sản phẩm
GET /api/products?my_products=true&user_id=1&tag_id=ABC
Authorization: Bearer {access_token}

# Chi tiết sản phẩm
GET /api/products/{id}
Authorization: Bearer {access_token}

# Cập nhật sản phẩm
POST /api/products/{id}
Authorization: Bearer {access_token}
Content-Type: multipart/form-data

# Xóa sản phẩm
DELETE /api/products/{id}
Authorization: Bearer {access_token}

# Thống kê
GET /api/products/statistics
Authorization: Bearer {access_token}
```

### NFC Routes (Public - Không cần token)

```http
# Xem thông tin sản phẩm qua NFC scan
GET /api/nfc/{tag_id}

# Đổi chủ sở hữu (DEPRECATED)
# Endpoint này đã ngừng hỗ trợ và sẽ trả về 410 Gone.
# Vui lòng dùng flow OTP bên dưới (/api/owner/*).
PUT /api/nfc/{tag_id}/owner
```

### Owner OTP Routes (Public - Không cần token)

> MỌI thay đổi OWNER đều BẮT BUỘC xác thực OTP qua email.
> - Send OTP KHÔNG update bảng `products`
> - OTP dùng 1 lần, hết hạn sau 5 phút
> - 1 email: tối đa 3 OTP / 10 phút (chống spam)

#### 1) Gửi OTP

```http
POST /api/owner/send-otp
Content-Type: application/json

{
    "product_id": 1,
    "email": "owner@example.com",
    "purpose": "update_owner"
}
```

#### 2) Submit cập nhật OWNER (verify OTP rồi mới update)

**CASE 1: Lần đầu update (owner_email = NULL)**

```http
PUT /api/owner/update
Content-Type: application/json

{
    "product_id": 1,
    "owner_name": "Tên chủ sở hữu",
    "owner_email": "owner@example.com",
    "otp_code": "123456"
}
```

**CASE 2: Đã có owner email – không đổi email**

```http
PUT /api/owner/update
Content-Type: application/json

{
    "product_id": 1,
    "owner_name": "Tên mới",
    "owner_email": "current@example.com",
    "otp_code": "123456"
}
```

**CASE 3: Đổi owner email (cần OTP cho cả email cũ và mới)**

```http
PUT /api/owner/update
Content-Type: application/json

{
    "product_id": 1,
    "owner_name": "Tên mới",
    "owner_email": "old@example.com",
    "owner_email_new": "new@example.com",
    "otp_code_old": "111111",
    "otp_code_new": "222222"
}
```

## 📱 Hệ thống NFC Tag

1. **Upload sản phẩm** → Nhận `nfc_url` (VD: `http://domain.com/nfc/NFC-A7B2C9`)
2. **Ghi URL vào chip NFC** → Khách scan để xem thông tin
3. **Đổi chủ sở hữu** → Flow OTP qua `/api/owner/send-otp` + `/api/owner/update`

**Tag ID:** Auto-generate dạng `NFC-XXXXXX`, unique, không thể sửa sau khi tạo.

## 🏗️ Cấu trúc Project

```
app/
├── Helpers/JWTHelper.php
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php      # Auth & User Management
│   │   ├── PartnerController.php   # Partner Management
│   │   ├── ProductController.php   # Product & NFC
│   │   └── OwnerController.php     # Owner update via OTP
│   └── Middleware/
│       ├── JWTAuthMiddleware.php
│       └── CheckRole.php
├── Mail/
│   └── OwnerOtpMail.php
└── Models/
    ├── User.php                    # với created_by tracking
    ├── Partner.php
    ├── Product.php
    └── EmailOtp.php
Services/
└── EmailOtpService.php
database/migrations/
├── create_users_table.php
├── create_partners_table.php       # name, domain, brand
├── create_products_table.php
├── add_role_to_users_table.php
├── add_partner_id_to_users_table.php
├── add_created_by_to_users_table.php  # Tracking người tạo
├── add_owner_email_verified_at_to_products_table.php
└── create_email_otps_table.php
```

## 🔒 Security

- Bcrypt password hashing
- JWT tokens với expiration
- Role-based Access Control (RBAC)
- Partner inheritance & cascade updates
- Created-by tracking system
- Middleware protection cho protected routes

## 🎯 Use Cases & Logic

### 1. Partner Inheritance System

**Khi tạo USER:**
- `partner_id` TỰ ĐỘNG kế thừa từ người tạo
- `created_by` lưu ID người tạo
- KHÔNG cho phép manual set partner_id

**Khi tạo PARTNER/ADMIN:**
- Cho phép manual set `partner_id` qua request
- Nếu không set → `partner_id = null`

### 2. Partner Update Cascade

**Khi Admin/Partner đổi partner_id:**
```
Partner A (partner_id = 1) → Đổi thành partner_id = 2
→ TẤT CẢ user (role="user") do Partner A tạo cũng đổi partner_id = 2
```

### 3. Quyền hạn theo Role

| Action | Admin | Partner | User |
|--------|-------|---------|------|
| Tạo Admin | ✅ | ❌ | ❌ |
| Tạo Partner | ✅ | ❌ | ❌ |
| Tạo User | ✅ | ✅ | ❌ |
| Đổi partner_id (của chính mình) | ✅ | ✅ | ❌ |
| Xóa Partner | ✅ | ❌ | ❌ |

### 4. Data Structure

**Partners:** `id, name, domain (unique), brand`

**Users:** `id, name, email, password, role, partner_id, created_by`

**Relationships:**
- `User belongsTo Partner`
- `User belongsTo User (creator)`
- `Partner hasMany Users`
- `User hasMany Users (created users)`

## 📝 License

MIT License

## 👤 Author

npbtruong - [GitHub](https://github.com/npbtruong)
