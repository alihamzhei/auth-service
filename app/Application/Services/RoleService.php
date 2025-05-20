<?php

namespace App\Application\Services;

use App\Domain\UseCases\Authorization\AssignRole;
use App\Domain\UseCases\Authorization\GetUserRoles;

class RoleService
{
    private AssignRole $assignRole;
    private GetUserRoles $getUserRoles;

    public function __construct(
        AssignRole $assignRole,
        GetUserRoles $getUserRoles
    ) {
        $this->assignRole = $assignRole;
        $this->getUserRoles = $getUserRoles;
    }

    public function assignRoleToUser(string $userId, string $roleName): void
    {
        $this->assignRole->execute($userId, $roleName);
    }

    public function getUserRoles(string $userId): array
    {
        return $this->getUserRoles->execute($userId);
    }
}