<?php

namespace App\Domain\UseCases\Auth;

use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Interfaces\TokenStorageInterface;
use App\Models\User as UserModel;

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
        
        // Get the Eloquent model for JWT auth
        $userModel = UserModel::where('email', $email)->first();
        
        // Generate JWT token
        $token = auth()->login($userModel);
        
        // Generate refresh token
        $refreshToken = bin2hex(random_bytes(32));
        
        // Store refresh token (use getKey() which returns the primary key - UUID)
        $this->tokenStorage->storeRefreshToken($userModel->getKey(), $refreshToken, 60 * 24 * 30); // 30 days
        
        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }
}