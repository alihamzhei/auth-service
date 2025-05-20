<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;
    public function findByName(string $name): ?Role;
    public function save(Role $role): Role;
    public function findAll(): array;
}