<?php

namespace App\Domain\UseCases\Auth;

use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Interfaces\TokenStorageInterface;

class LoginUser
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

    public function execute(string $email, string $password): array
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !password_verify($password, $user->getPassword())) {
            throw new \Exception('Invalid credentials');
        }
        
        // Generate JWT token
        $token = auth()->claims([
            'sub' => $user->getId(),
            'email' => $user->getEmail()
        ])->login($user);
        
        // Generate refresh token
        $refreshToken = bin2hex(random_bytes(32));
        
        // Store refresh token
        $this->tokenStorage->storeRefreshToken($user->getId(), $refreshToken, 60 * 24 * 30); // 30 days
        
        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }
}