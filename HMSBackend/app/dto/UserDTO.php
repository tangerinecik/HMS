<?php

namespace App\DTO;

class UserDTO
{
    public int $id;
    public string $email;
    public string $firstName;
    public string $lastName;
    public ?string $phone;
    public string $role;
    public bool $isActive;
    public bool $emailVerified;
    public string $createdAt;

    public function __construct(array $userData)
    {
        $this->id = (int)$userData['id'];
        $this->email = $userData['email'];
        $this->firstName = $userData['first_name'];
        $this->lastName = $userData['last_name'];
        $this->phone = $userData['phone'] ?? null;
        $this->role = $userData['role'];
        $this->isActive = (bool)$userData['is_active'];
        $this->emailVerified = (bool)($userData['email_verified'] ?? false);
        $this->createdAt = $userData['created_at'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'email_verified' => $this->emailVerified,
            'created_at' => $this->createdAt
        ];
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}
