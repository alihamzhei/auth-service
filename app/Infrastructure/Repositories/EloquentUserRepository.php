<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\User as UserEntity;
use App\Domain\Interfaces\UserRepositoryInterface;
use App\Models\User as UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?UserEntity
    {
        $user = UserModel::where('uuid', $id)->first();
        
        if (!$user) {
            return null;
        }
        
        return $this->mapToEntity($user);
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $user = UserModel::where('email', $email)->first();
        
        if (!$user) {
            return null;
        }
        
        return $this->mapToEntity($user);
    }

    public function save(UserEntity $user): UserEntity
    {
        // For new users, create with Domain-generated UUID
        // For existing users, find by UUID and update
        $model = UserModel::updateOrCreate(
            ['uuid' => $user->getUuid()],
            [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'password' => $user->getPassword(),
            ]
        );
        
        // Assign roles
        foreach ($user->getRoles() as $role) {
            $model->assignRole($role->getName());
        }
        
        return $this->mapToEntity($model);
    }

    private function mapToEntity(UserModel $model): UserEntity
    {
        $user = new UserEntity($model->name, $model->email, $model->password, $model->uuid);
        
        // Map roles
        foreach ($model->roles as $role) {
            $roleEntity = new \App\Domain\Entities\Role($role->name);
            $user->addRole($roleEntity);
        }
        
        return $user;
    }
}