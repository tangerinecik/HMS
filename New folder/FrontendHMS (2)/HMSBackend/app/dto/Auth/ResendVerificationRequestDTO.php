<?php

namespace App\DTO\Auth;

class ResendVerificationRequestDTO
{
    public string $email;

    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        return $errors;
    }
}
