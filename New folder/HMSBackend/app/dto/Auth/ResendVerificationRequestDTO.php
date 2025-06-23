<?php

namespace App\DTO\Auth;

use App\DTO\BaseDTO;

class ResendVerificationRequestDTO extends BaseDTO
{
    public string $email;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        return $dto;
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateEmail($this->email)
        ]);
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email
        ];
    }
}
