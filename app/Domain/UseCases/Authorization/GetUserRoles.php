<?php

namespace App\Domain\UseCases\Authorization;

use App\Domain\Interfaces\UserRepositoryInterface;

class GetUserRoles
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(string $userId): array
    {
        // Find user
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \Exception('User not found');
        }

        // Get user roles
        $roles = $user->getRoles();
        
        // Format roles for response
        $formattedRoles = [];
        foreach ($roles as $role) {
            $formattedRoles[] = [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'permissions' => array_map(function ($permission) {
                    return [
                        'id' => $permission->getId(),
                        'name' => $permission->getName()
                    ];
                }, $role->getPermissions())
            ];
        }

        return $formattedRoles;
    }
}