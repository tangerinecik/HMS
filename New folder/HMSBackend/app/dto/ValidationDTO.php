<?php

namespace App\DTO;

class ValidationErrorDTO
{
    public string $field;
    public string $message;

    public function __construct(string $field, string $message)
    {
        $this->field = $field;
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'message' => $this->message
        ];
    }
}

class ValidationResponseDTO
{
    /** @var ValidationErrorDTO[] */
    public array $errors;
    public bool $isValid;

    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
        $this->isValid = empty($errors);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[] = new ValidationErrorDTO($field, $message);
        $this->isValid = false;
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => array_map(function($error) {
                return $error->toArray();
            }, $this->errors)
        ];
    }
}
