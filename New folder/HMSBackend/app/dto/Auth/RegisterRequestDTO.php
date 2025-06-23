<?php

namespace App\DTO\Auth;

use App\DTO\BaseDTO;

class RegisterRequestDTO extends BaseDTO
{
    public string $email;
    public string $password;
    public string $firstName;
    public string $lastName;
    public ?string $phone;
    public string $role;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->firstName = $data['first_name'] ?? '';
        $dto->lastName = $data['last_name'] ?? '';
        $dto->phone = $data['phone'] ?? null;
        $dto->role = $data['role'] ?? 'customer';
        return $dto;
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateEmail($this->email),
            fn() => $this->validateMinLength($this->password, 8, 'password'),
            fn() => $this->validateRequired($this->firstName, 'first name'),
            fn() => $this->validateRequired($this->lastName, 'last name'),
            fn() => $this->validateInArray($this->role, ['customer', 'employee', 'admin'], 'role'),
            fn() => $this->validatePhone($this->phone)
        ]);
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'role' => $this->role
        ];
    }
}
