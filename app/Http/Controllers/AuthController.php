<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\JWTHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request)
    {
        //tạm chưa sai api này
        return response()->json(['message' => 'Đăng ký tạm thời không khả dụng'], 503);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Đăng ký công khai chỉ tạo user với role 'user'
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // Mặc định là user
        ]);

        $token = JWTHelper::generateToken($user->id);
        $refreshToken = JWTHelper::generateRefreshToken($user->id);

        return response()->json([
            'message' => 'Đăng ký thành công',
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng'], 401);
        }

        $token = JWTHelper::generateToken($user->id);
        $refreshToken = JWTHelper::generateRefreshToken($user->id);

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Refresh access token
     */
    public function refreshToken(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return response()->json(['message' => 'Refresh token không được cung cấp'], 400);
        }

        try {
            $decoded = JWTHelper::verifyToken($refreshToken);

            if (($decoded->type ?? null) !== 'refresh') {
                return response()->json(['message' => 'Token không hợp lệ'], 401);
            }

            $userId = $decoded->sub;
            $newToken = JWTHelper::generateToken($userId);

            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'domain' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['name', 'email', 'domain']));

        return response()->json([
            'message' => 'Cập nhật thông tin thành công',
            'user' => $user,
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 400);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Đổi mật khẩu thành công']);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Với JWT stateless, logout chỉ cần client xóa token
        // Có thể implement blacklist token nếu cần
        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Xác thực mật khẩu trước khi xóa
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu không đúng'], 400);
        }

        // Xóa user
        $user->delete();

        return response()->json(['message' => 'Tài khoản đã được xóa thành công'], 200);
    }

    /**
     * Create new user (Admin/Partner only)
     * Admin có thể tạo user và partner
     * Partner chỉ có thể tạo user
     */
    public function createUser(Request $request)
    {
        $currentUser = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:user,partner,admin',
            'domain' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $requestedRole = $request->role;

        // Kiểm tra quyền tạo user
        if ($requestedRole === 'admin') {
            // Chỉ admin mới có thể tạo admin
            if (!$currentUser->isAdmin()) {
                return response()->json([
                    'message' => 'Bạn không có quyền tạo tài khoản Admin'
                ], 403);
            }
        } elseif ($requestedRole === 'partner') {
            // Chỉ admin mới có thể tạo partner
            if (!$currentUser->canCreatePartner()) {
                return response()->json([
                    'message' => 'Bạn không có quyền tạo tài khoản Partner'
                ], 403);
            }
        } elseif ($requestedRole === 'user') {
            // Admin và Partner có thể tạo user
            if (!$currentUser->canCreateUser()) {
                return response()->json([
                    'message' => 'Bạn không có quyền tạo tài khoản User'
                ], 403);
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $requestedRole,
            'domain' => $request->domain,
        ]);

        return response()->json([
            'message' => 'Tạo tài khoản thành công',
            'user' => $user,
        ], 201);
    }
}
