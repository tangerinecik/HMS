<?php

namespace App\DTO\Auth;

use App\DTO\UserDTO;

class AuthResponseDTO
{
    public string $token;
    public UserDTO $user;
    public string $message;

    public function __construct(string $token, UserDTO $user, string $message = 'Authentication successful')
    {
        $this->token = $token;
        $this->user = $user;
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'user' => $this->user->toArray(),
            'message' => $this->message
        ];
    }
}
