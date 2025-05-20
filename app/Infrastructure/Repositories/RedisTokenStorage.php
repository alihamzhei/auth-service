<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Interfaces\TokenStorageInterface;
use Illuminate\Support\Facades\Redis;

class RedisTokenStorage implements TokenStorageInterface
{
    public function storeRefreshToken(string $userId, string $token, int $ttl): void
    {
        Redis::setex("refresh_token:{$userId}:{$token}", $ttl, 'valid');
    }

    public function validateRefreshToken(string $userId, string $token): bool
    {
        return Redis::exists("refresh_token:{$userId}:{$token}");
    }

    public function invalidateRefreshToken(string $userId, string $token): void
    {
        Redis::del("refresh_token:{$userId}:{$token}");
    }
}