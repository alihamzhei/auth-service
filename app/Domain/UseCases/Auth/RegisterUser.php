<?php

namespace App\Domain\UseCases\Auth;

use App\Domain\Entities\User;
use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Interfaces\RoleRepositoryInterface;

class RegisterUser
{
    private UserRepositoryInterface $userRepository;
    private RoleRepositoryInterface $roleRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        RoleRepositoryInterface $roleRepository
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function execute(string $name, string $email, string $password): User
    {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Create a new user
        $user = new User($name, $email, $hashedPassword);
        
        // Assign default role
        $defaultRole = $this->roleRepository->findByName('user');
        if ($defaultRole) {
            $user->addRole($defaultRole);
        }
        
        // Save user
        return $this->userRepository->save($user);
    }
}