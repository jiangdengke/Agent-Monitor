<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TokenService
{
    private const TOKEN_PREFIX = 'auth_token:';
    private const TOKEN_TTL = 60 * 60 * 24 * 7; // 7 天

    /**
     * 创建 Token
     */
    public function createToken(User $user): string
    {
        $token = Str::random(64);

        Cache::put(
            self::TOKEN_PREFIX . $token,
            $user->id,
            self::TOKEN_TTL
        );

        return $token;
    }

    /**
     * 验证 Token 并返回用户
     */
    public function validateToken(string $token): ?User
    {
        $userId = Cache::get(self::TOKEN_PREFIX . $token);

        if (!$userId) {
            return null;
        }

        // 刷新 Token 过期时间
        Cache::put(
            self::TOKEN_PREFIX . $token,
            $userId,
            self::TOKEN_TTL
        );

        return User::find($userId);
    }

    /**
     * 删除 Token
     */
    public function deleteToken(string $token): void
    {
        Cache::forget(self::TOKEN_PREFIX . $token);
    }
}
