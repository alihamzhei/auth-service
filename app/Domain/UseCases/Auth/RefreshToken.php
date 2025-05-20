<?php

namespace App\Domain\UseCases\Auth;

use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Interfaces\TokenStorageInterface;

class RefreshToken
{
    private UserRepositoryInterface $userRepository;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        UserRepositoryInterface $userRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
    }

    public function execute(string $userId, string $refreshToken): array
    {
        // Validate refresh token
        if (!$this->tokenStorage->validateRefreshToken($userId, $refreshToken)) {
            throw new \Exception('Invalid refresh token');
        }

        // Find user
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \Exception('User not found');
        }

        // Invalidate old refresh token
        $this->tokenStorage->invalidateRefreshToken($userId, $refreshToken);

        // Generate new refresh token
        $newRefreshToken = bin2hex(random_bytes(32));
        
        // Store new refresh token (valid for 7 days)
        $this->tokenStorage->storeRefreshToken($userId, $newRefreshToken, 7 * 24 * 60 * 60);

        return [
            'userId' => $userId,
            'refreshToken' => $newRefreshToken
        ];
    }
}