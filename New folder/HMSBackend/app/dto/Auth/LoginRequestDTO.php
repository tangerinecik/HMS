<?php

namespace App\DTO\Auth;

use App\DTO\BaseDTO;

class LoginRequestDTO extends BaseDTO
{
    public string $email;
    public string $password;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        return $dto;
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateEmail($this->email),
            fn() => $this->validateRequired($this->password, 'password')
        ]);
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password
        ];
    }
}
