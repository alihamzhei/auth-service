<?php

namespace App\Domain\Interfaces;

interface TokenStorageInterface
{
    public function storeRefreshToken(string $userId, string $token, int $ttl): void;
    public function validateRefreshToken(string $userId, string $token): bool;
    public function invalidateRefreshToken(string $userId, string $token): void;
    public function invalidateAllUserTokens(string $userId): void;
}