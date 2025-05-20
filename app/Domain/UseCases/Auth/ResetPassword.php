<?php

namespace App\Domain\UseCases\Auth;

use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Interfaces\TokenStorageInterface;

class ResetPassword
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

    public function initiateReset(string $email): string
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \Exception('User not found');
        }

        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        
        // Store reset token (valid for 1 hour)
        $this->tokenStorage->storeRefreshToken($user->getId(), $resetToken, 60 * 60);

        return $resetToken;
    }

    public function completeReset(string $userId, string $resetToken, string $newPassword): void
    {
        // Validate reset token
        if (!$this->tokenStorage->validateRefreshToken($userId, $resetToken)) {
            throw new \Exception('Invalid or expired reset token');
        }

        // Find user
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \Exception('User not found');
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->setPassword($hashedPassword);
        
        // Save user
        $this->userRepository->update($user);
        
        // Invalidate reset token
        $this->tokenStorage->invalidateRefreshToken($userId, $resetToken);
        
        // Invalidate all user tokens (force logout from all devices)
        $this->tokenStorage->invalidateAllUserTokens($userId);
    }
}