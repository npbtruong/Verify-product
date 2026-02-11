<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\OwnerController;


Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes - Require JWT authentication
Route::middleware(['jwt.auth'])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

        // Tạo user mới (Admin và Partner)
        Route::post('/create-user', [AuthController::class, 'createUser'])
            ->middleware('role:admin,partner');
    });

    // Partner routes (Admin và Partner có quyền tạo/sửa, chỉ Admin mới xóa được)
    Route::prefix('partners')->group(function () {
        Route::get('/', [PartnerController::class, 'index']); // Lấy danh sách partners
        Route::get('/{id}', [PartnerController::class, 'show']); // Chi tiết partner
        Route::post('/', [PartnerController::class, 'store'])
            ->middleware('role:admin,partner'); // Tạo partner
        Route::put('/{id}', [PartnerController::class, 'update'])
            ->middleware('role:admin,partner'); // Cập nhật partner
        Route::delete('/{id}', [PartnerController::class, 'destroy'])
            ->middleware('role:admin'); // Chỉ admin mới xóa được
    });

    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); // Lấy danh sách sản phẩm
        Route::post('/', [ProductController::class, 'store']); // Upload sản phẩm
        Route::put('/{tag_id}/nfc-written', [ProductController::class, 'markNfcWritten']); // Đánh dấu NFC đã ghi
        Route::get('/statistics', [ProductController::class, 'statistics']); // Thống kê
        Route::get('/{tag_id}', [ProductController::class, 'show']); // Chi tiết sản phẩm
        Route::post('/{id}', [ProductController::class, 'update']); // Cập nhật sản phẩm (dùng POST vì có upload file)
        Route::delete('/{id}', [ProductController::class, 'destroy']); // Xóa sản phẩm
    });
});
// -----END----- Protected routes


// NFC Routes - Public 
Route::prefix('nfc')->group(function () {
    Route::get('/{tag_id}', [ProductController::class, 'getByTag']); // Xem thông tin qua NFC scan
});

// Owner OTP Routes - Public
Route::prefix('owner')->group(function () {
    Route::post('/send-otp', [OwnerController::class, 'sendOtp'])
        ->middleware('throttle:6,1');
    Route::put('/update', [OwnerController::class, 'update'])
        ->middleware('throttle:10,1');
});
