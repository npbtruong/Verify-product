<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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
    });

    // Thêm các protected routes khác của bạn tại đây
    // Example:
    // Route::apiResource('posts', PostController::class);
    // Route::apiResource('products', ProductController::class);
    // Route::get('/users', [UserController::class, 'index']);
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
