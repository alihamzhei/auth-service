<?php

namespace App\Domain\UseCases\Authorization;

use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Interfaces\RoleRepositoryInterface;

class AssignRole
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

    public function execute(string $userId, string $roleName): void
    {
        // Find user
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \Exception('User not found');
        }

        // Find role
        $role = $this->roleRepository->findByName($roleName);
        if ($role === null) {
            throw new \Exception('Role not found');
        }

        // Check if user already has this role
        if ($user->hasRole($roleName)) {
            return; // User already has this role
        }

        // Assign role to user
        $user->addRole($role);
        
        // Save user
        $this->userRepository->update($user);
    }
}