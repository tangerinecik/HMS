<?php

namespace App\DTO\Auth;

class LoginRequestDTO
{
    public string $email;
    public string $password;

    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
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
        }

        return $errors;
    }
}
