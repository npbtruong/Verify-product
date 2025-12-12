# BE Mobile - Laravel JWT Authentication API

Backend API cho ứng dụng mobile sử dụng Laravel 11 và JWT Authentication.

## 🚀 Tính năng

- ✅ JWT Authentication (Access Token + Refresh Token)
- ✅ User Registration & Login
- ✅ Profile Management
- ✅ Change Password
- ✅ Token Refresh
- ✅ Protected Routes với Middleware

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
php artisan jwt:secret

# Chạy migration
php artisan migrate

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

## 🏗️ Cấu trúc Project

```
laravel/
├── app/
│   ├── Helpers/
│   │   └── JWTHelper.php          # JWT utility functions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── AuthController.php  # Authentication controller
│   │   └── Middleware/
│   │       └── JWTAuthMiddleware.php # JWT middleware
│   └── Models/
│       └── User.php
├── bootstrap/
│   └── app.php                     # Middleware registration
├── routes/
│   └── api.php                     # API routes
└── .env                            # Environment variables
```

## 🔒 Security

- Passwords được hash với bcrypt
- JWT tokens có thời gian hết hạn
- Refresh tokens riêng biệt với access tokens
- Middleware bảo vệ protected routes

## 📝 License

MIT License

## 👤 Author

npbtruong - [GitHub](https://github.com/npbtruong)
