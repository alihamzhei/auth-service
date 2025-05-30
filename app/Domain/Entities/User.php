<?php

namespace App\Domain\Entities;

use Ramsey\Uuid\Uuid;

class User
{
    private string $uuid;
    private string $name;
    private string $email;
    private string $password;
    private array $roles = [];

    public function __construct(string $name, string $email, string $password, ?string $uuid = null)
    {
        $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getId(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function addRole(Role $role): void
    {
        $this->roles[] = $role;
    }
}