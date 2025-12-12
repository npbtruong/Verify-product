<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

class JWTHelper
{
    /**
     * Tạo JWT token
     * 
     * @param int $userId
     * @param array $additionalData (optional)
     * @return string
     */
    public static function generateToken(int $userId, array $additionalData = []): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 60 * 24 * 30); // 30 ngày

        $payload = array_merge([
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
            'iss' => config('app.url'),
        ], $additionalData);

        return JWT::encode($payload, self::getSecretKey(), 'HS256');
    }

    /**
     * Tạo refresh token (thời hạn dài hơn)
     * 
     * @param int $userId
     * @return string
     */
    public static function generateRefreshToken(int $userId): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 60 * 24 * 90); // 90 ngày

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
            'iss' => config('app.url'),
            'type' => 'refresh'
        ];

        return JWT::encode($payload, self::getSecretKey(), 'HS256');
    }

    /**
     * Xác thực và decode JWT token
     * 
     * @param string $token
     * @return object|null
     */
    public static function verifyToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
        } catch (ExpiredException $e) {
            throw new Exception('Token đã hết hạn', 401);
        } catch (Exception $e) {
            throw new Exception('Token không hợp lệ', 401);
        }
    }

    /**
     * Lấy user ID từ token
     * 
     * @param string $token
     * @return int|null
     */
    public static function getUserIdFromToken(string $token): ?int
    {
        try {
            $decoded = self::verifyToken($token);
            return $decoded->sub ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Kiểm tra token có hợp lệ không
     * 
     * @param string $token
     * @return bool
     */
    public static function isTokenValid(string $token): bool
    {
        try {
            self::verifyToken($token);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Lấy secret key từ config
     * 
     * @return string
     */
    private static function getSecretKey(): string
    {
        return config('jwt.secret') ?: env('JWT_SECRET', config('app.key'));
    }

    /**
     * Parse token từ header Authorization
     * 
     * @param string|null $authHeader
     * @return string|null
     */
    public static function parseTokenFromHeader(?string $authHeader): ?string
    {
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }
}
