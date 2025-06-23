<?php

namespace App\DTO;

class UserDTO extends BaseDTO
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

    public static function fromArray(array $userData): self
    {
        $dto = new self();
        $dto->id = (int)$userData['id'];
        $dto->email = $userData['email'];
        $dto->firstName = $userData['first_name'];
        $dto->lastName = $userData['last_name'];
        $dto->phone = $userData['phone'] ?? null;
        $dto->role = $userData['role'];
        $dto->isActive = (bool)$userData['is_active'];
        $dto->emailVerified = (bool)($userData['email_verified'] ?? false);
        $dto->createdAt = $userData['created_at'];
        return $dto;
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateEmail($this->email),
            fn() => $this->validateRequired($this->firstName, 'first name'),
            fn() => $this->validateRequired($this->lastName, 'last name'),
            fn() => $this->validateInArray($this->role, ['customer', 'employee', 'admin'], 'role')
        ]);
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
