<?php

namespace App\Domain\Entities;

use Ramsey\Uuid\Uuid;

class User
{
    private string $id;
    private string $email;
    private string $password;
    private array $roles = [];

    public function __construct(string $email, string $password)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->email = $email;
        $this->password = $password;
    }

    public function getId(): string
    {
        return $this->id;
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