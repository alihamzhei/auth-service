<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\User as UserEntity;
use App\Domain\Interfaces\UserRepositoryInterface;
use App\Models\User as UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?UserEntity
    {
        $user = UserModel::find($id);
        
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
        $model = UserModel::updateOrCreate(
            ['id' => $user->getId()],
            [
                'email' => $user->getEmail(),
                'password' => $user->getPassword()
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
        $user = new UserEntity($model->email, $model->password);
        
        // Map roles
        foreach ($model->roles as $role) {
            $roleEntity = new \App\Domain\Entities\Role($role->name);
            $user->addRole($roleEntity);
        }
        
        return $user;
    }
}