<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JWTHelper;
use App\Models\User;
use Exception;

class JWTAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Lấy token từ header Authorization
        $authHeader = $request->header('Authorization');
        $token = JWTHelper::parseTokenFromHeader($authHeader);

        if (!$token) {
            return response()->json([
                'message' => 'Token không được cung cấp'
            ], 401);
        }

        try {
            // Xác thực token
            $decoded = JWTHelper::verifyToken($token);

            // Kiểm tra xem có phải là refresh token không (không cho phép dùng refresh token để truy cập API)
            if (($decoded->type ?? null) === 'refresh') {
                return response()->json([
                    'message' => 'Không thể sử dụng refresh token để truy cập API'
                ], 401);
            }

            // Lấy user từ database
            $userId = $decoded->sub;
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'message' => 'Người dùng không tồn tại'
                ], 401);
            }

            // Gắn user vào request để sử dụng trong controller
            $request->merge(['auth_user' => $user]);
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            return $next($request);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        }
    }
}
