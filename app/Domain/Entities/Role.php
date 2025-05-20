<?php

namespace App\Domain\Entities;

class Role
{
    private int $id;
    private string $name;
    private array $permissions = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): void
    {
        $this->permissions[] = $permission;
    }
}