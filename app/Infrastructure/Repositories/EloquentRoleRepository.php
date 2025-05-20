<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Role;
use App\Domain\Entities\Permission;
use App\Domain\Interfaces\RoleRepositoryInterface;
use Spatie\Permission\Models\Role as EloquentRole;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findById(int $id): ?Role
    {
        $eloquentRole = EloquentRole::find($id);
        if ($eloquentRole === null) {
            return null;
        }

        return $this->mapToDomainEntity($eloquentRole);
    }

    public function findByName(string $name): ?Role
    {
        $eloquentRole = EloquentRole::where('name', $name)->first();
        if ($eloquentRole === null) {
            return null;
        }

        return $this->mapToDomainEntity($eloquentRole);
    }

    public function save(Role $role): Role
    {
        $eloquentRole = EloquentRole::create(['name' => $role->getName(), 'guard_name' => 'api']);
        
        // Sync permissions
        $permissionNames = array_map(function ($permission) {
            return $permission->getName();
        }, $role->getPermissions());
        
        $eloquentRole->syncPermissions($permissionNames);
        
        // Update domain entity with ID
        $role->setId($eloquentRole->id);
    
        // Return the domain entity
        return $this->mapToDomainEntity($eloquentRole);
    }

    public function update(Role $role): void
    {
        $eloquentRole = EloquentRole::find($role->getId());
        if ($eloquentRole === null) {
            throw new \Exception('Role not found');
        }

        $eloquentRole->name = $role->getName();
        $eloquentRole->save();

        // Sync permissions
        $permissionNames = array_map(function ($permission) {
            return $permission->getName();
        }, $role->getPermissions());
        
        $eloquentRole->syncPermissions($permissionNames);
    }

    public function delete(int $id): void
    {
        $eloquentRole = EloquentRole::find($id);
        if ($eloquentRole === null) {
            throw new \Exception('Role not found');
        }

        $eloquentRole->delete();
    }

    public function findAll(): array
    {
        $eloquentRoles = EloquentRole::all();
        
        $roles = [];
        foreach ($eloquentRoles as $eloquentRole) {
            $roles[] = $this->mapToDomainEntity($eloquentRole);
        }
        
        return $roles;
    }

    private function mapToDomainEntity(EloquentRole $eloquentRole): Role
    {
        $role = new Role($eloquentRole->name);
        $role->setId($eloquentRole->id);
        
        // Map permissions
        foreach ($eloquentRole->permissions as $eloquentPermission) {
            $permission = new Permission($eloquentPermission->name);
            $permission->setId($eloquentPermission->id);
            $role->addPermission($permission);
        }
        
        return $role;
    }
}