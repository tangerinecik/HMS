<?php

namespace App\DTO\Auth;

class RegisterRequestDTO
{
    public string $email;
    public string $password;
    public string $firstName;
    public string $lastName;
    public ?string $phone;
    public string $role;

    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->phone = $data['phone'] ?? null;
        $this->role = $data['role'] ?? 'customer';
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($this->password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($this->password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (empty($this->firstName)) {
            $errors[] = 'First name is required';
        }

        if (empty($this->lastName)) {
            $errors[] = 'Last name is required';
        }

        if (!in_array($this->role, ['customer', 'employee', 'admin'])) {
            $errors[] = 'Invalid role';
        }

        if ($this->phone && !preg_match('/^\+?[1-9]\d{1,14}$/', $this->phone)) {
            $errors[] = 'Invalid phone number format';
        }

        return $errors;
    }
}
