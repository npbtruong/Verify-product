<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Tất cả routes sẽ có prefix /api tự động
| VD: POST http://localhost:8000/api/auth/login
*/

// ========================================
// PUBLIC ROUTES - Không cần authentication
// ========================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);
});

// NFC Routes - Public (không cần authentication)
Route::prefix('nfc')->group(function () {
    Route::get('/{tag_id}', [ProductController::class, 'getByTag']); // Xem thông tin qua NFC scan
    Route::put('/{tag_id}/owner', [ProductController::class, 'updateOwner']); // Đổi chủ sở hữu
});

// ========================================
// PROTECTED ROUTES - Cần JWT authentication
// ========================================
Route::middleware(['jwt.auth'])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);
    });

    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); // Lấy danh sách sản phẩm
        Route::post('/', [ProductController::class, 'store']); // Upload sản phẩm
        Route::get('/statistics', [ProductController::class, 'statistics']); // Thống kê
        Route::get('/{id}', [ProductController::class, 'show']); // Chi tiết sản phẩm
        Route::post('/{id}', [ProductController::class, 'update']); // Cập nhật sản phẩm (dùng POST vì có upload file)
        Route::delete('/{id}', [ProductController::class, 'destroy']); // Xóa sản phẩm
    });
});

// ========================================
// UTILITY ROUTES
// ========================================
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API is running',
        'timestamp' => now()->toIso8601String(),
        'laravel_version' => app()->version(),
    ]);
});

// Test route để kiểm tra API
Route::get('/test', function () {
    return response()->json([
        'message' => 'API test successful',
        'time' => now()->format('Y-m-d H:i:s'),
    ]);
});
