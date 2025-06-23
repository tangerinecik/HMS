<?php

namespace App\DTO\Auth;

use App\DTO\BaseDTO;

class EmailVerificationRequestDTO extends BaseDTO
{
    public string $token;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->token = $data['token'] ?? '';
        return $dto;
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateRequired($this->token, 'verification token')
        ]);
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token
        ];
    }
}
