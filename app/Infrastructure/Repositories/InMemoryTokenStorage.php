<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Interfaces\TokenStorageInterface;

class InMemoryTokenStorage implements TokenStorageInterface
{
    private static array $tokens = [];

    public function storeRefreshToken(string $userId, string $token, int $ttl): void
    {
        $expiresAt = time() + ($ttl * 60); // TTL is in minutes
        $key = "refresh_token:{$userId}:{$token}";
        self::$tokens[$key] = [
            'expires_at' => $expiresAt
        ];
        file_put_contents('/tmp/auth-debug.log', date('Y-m-d H:i:s') . " STORED KEY: " . $key . " EXPIRES: " . $expiresAt . "\n", FILE_APPEND);
    }

    public function validateRefreshToken(string $userId, string $token): bool
    {
        $key = "refresh_token:{$userId}:{$token}";
        file_put_contents('/tmp/auth-debug.log', date('Y-m-d H:i:s') . " VALIDATING KEY: " . $key . "\n", FILE_APPEND);
        file_put_contents('/tmp/auth-debug.log', date('Y-m-d H:i:s') . " AVAILABLE KEYS: " . implode(', ', array_keys(self::$tokens)) . "\n", FILE_APPEND);
        
        if (!isset(self::$tokens[$key])) {
            file_put_contents('/tmp/auth-debug.log', date('Y-m-d H:i:s') . " KEY NOT FOUND\n", FILE_APPEND);
            return false;
        }
        
        // Check if token has expired
        if (time() > self::$tokens[$key]['expires_at']) {
            file_put_contents('/tmp/auth-debug.log', date('Y-m-d H:i:s') . " TOKEN EXPIRED\n", FILE_APPEND);
            unset(self::$tokens[$key]);
            return false;
        }
        
        file_put_contents('/tmp/auth-debug.log', date('Y-m-d H:i:s') . " TOKEN VALID\n", FILE_APPEND);
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
}