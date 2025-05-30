<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Interfaces\TokenStorageInterface;

class FileTokenStorage implements TokenStorageInterface
{
    private string $storageDir;

    public function __construct()
    {
        $this->storageDir = storage_path('app/refresh_tokens');
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function storeRefreshToken(string $userId, string $token, int $ttl): void
    {
        $expiresAt = time() + ($ttl * 60); // TTL is in minutes
        $data = [
            'expires_at' => $expiresAt
        ];
        
        $filePath = $this->getTokenFilePath($userId, $token);
        file_put_contents($filePath, json_encode($data), LOCK_EX);
    }

    public function validateRefreshToken(string $userId, string $token): bool
    {
        $filePath = $this->getTokenFilePath($userId, $token);
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($filePath), true);
        
        if (!$data || !isset($data['expires_at'])) {
            return false;
        }
        
        // Check if token has expired
        if (time() > $data['expires_at']) {
            unlink($filePath);
            return false;
        }
        
        return true;
    }

    public function invalidateRefreshToken(string $userId, string $token): void
    {
        $filePath = $this->getTokenFilePath($userId, $token);
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function invalidateAllUserTokens(string $userId): void
    {
        $userDir = $this->storageDir . '/' . $this->sanitizeUserId($userId);
        
        if (is_dir($userDir)) {
            $files = glob($userDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($userDir);
        }
    }

    private function getTokenFilePath(string $userId, string $token): string
    {
        $sanitizedUserId = $this->sanitizeUserId($userId);
        $sanitizedToken = $this->sanitizeToken($token);
        
        $userDir = $this->storageDir . '/' . $sanitizedUserId;
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
        
        return $userDir . '/' . $sanitizedToken;
    }

    private function sanitizeUserId(string $userId): string
    {
        return preg_replace('/[^a-zA-Z0-9\-]/', '', $userId);
    }

    private function sanitizeToken(string $token): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $token);
    }
}