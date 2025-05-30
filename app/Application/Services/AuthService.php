<?php

namespace App\Application\Services;

use App\Application\DTOs\RegisterUserDTO;
use App\Application\DTOs\LoginUserDTO;
use App\Domain\UseCases\Auth\RegisterUser;
use App\Domain\UseCases\Auth\LoginUser;
use App\Domain\Interfaces\TokenStorageInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;

class AuthService
{
    private RegisterUser $registerUseCase;
    private LoginUser $loginUseCase;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        RegisterUser $registerUseCase,
        LoginUser $loginUseCase,
        TokenStorageInterface $tokenStorage
    ) {
        $this->registerUseCase = $registerUseCase;
        $this->loginUseCase = $loginUseCase;
        $this->tokenStorage = $tokenStorage;
    }

    public function register(RegisterUserDTO $dto): array
    {
        $user = $this->registerUseCase->execute(
            $dto->getName(),
            $dto->getEmail(),
            $dto->getPassword()
        );
        
        return [
            'id' => $user->getUuid(),
            'email' => $user->getEmail()
        ];
    }

    public function login(LoginUserDTO $dto): array
    {
        return $this->loginUseCase->execute(
            $dto->getEmail(),
            $dto->getPassword()
        );
    }

    public function refresh(string $userId, string $refreshToken): array
    {
        // Validate refresh token
        if (!$this->tokenStorage->validateRefreshToken($userId, $refreshToken)) {
            throw new \Exception('Invalid refresh token');
        }
        
        // Invalidate the old refresh token
        $this->tokenStorage->invalidateRefreshToken($userId, $refreshToken);
        
        // Generate new tokens
        $user = auth()->userOrFail();
        $token = JWTAuth::fromUser($user);
        
        // Generate new refresh token
        $newRefreshToken = Uuid::uuid4()->toString();
        $expiresIn = config('jwt.refresh_ttl', 20160); // Default: 2 weeks
        
        // Store new refresh token
        $this->tokenStorage->storeRefreshToken($userId, $newRefreshToken, $expiresIn);
        
        return [
            'access_token' => $token,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 60) * 60 // Default: 1 hour in seconds
        ];
    }

    public function logout(string $userId, string $refreshToken = null): void
    {
        if ($refreshToken) {
            // Invalidate specific refresh token
            $this->tokenStorage->invalidateRefreshToken($userId, $refreshToken);
        } else {
            // Invalidate all user tokens
            $this->tokenStorage->invalidateAllUserTokens($userId);
        }
        
        // Invalidate JWT token
        auth()->logout();
    }
}