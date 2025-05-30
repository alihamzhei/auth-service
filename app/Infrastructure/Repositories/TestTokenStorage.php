<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Interfaces\TokenStorageInterface;

class TestTokenStorage implements TokenStorageInterface
{
    private static array $tokens = [];

    public function storeRefreshToken(string $userId, string $token, int $ttl): void
    {
        $expiresAt = time() + ($ttl * 60); // TTL is in minutes
        $key = "refresh_token:{$userId}:{$token}";
        self::$tokens[$key] = [
            'expires_at' => $expiresAt
        ];
    }

    public function validateRefreshToken(string $userId, string $token): bool
    {
        $key = "refresh_token:{$userId}:{$token}";
        
        if (!isset(self::$tokens[$key])) {
            return false;
        }
        
        // Check if token has expired
        if (time() > self::$tokens[$key]['expires_at']) {
            unset(self::$tokens[$key]);
            return false;
        }
        
        return true;
    }

    public function invalidateRefreshToken(string $userId, string $token): void
    {
        $key = "refresh_token:{$userId}:{$token}";
        unset(self::$tokens[$key]);
    }

    public function invalidateAllUserTokens(string $userId): void
    {
        $prefix = "refresh_token:{$userId}:";
        
        foreach (self::$tokens as $key => $data) {
            if (str_starts_with($key, $prefix)) {
                unset(self::$tokens[$key]);
            }
        }
    }

    public static function clearAll(): void
    {
        self::$tokens = [];
    }
}