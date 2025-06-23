<?php

namespace App\DTO\Auth;

class EmailVerificationRequestDTO
{
    public string $token;

    public function __construct(array $data)
    {
        $this->token = $data['token'] ?? '';
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->token)) {
            $errors[] = 'Verification token is required';
        }

        return $errors;
    }
}
