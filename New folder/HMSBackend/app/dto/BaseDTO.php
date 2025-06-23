<?php

namespace App\DTO;

use App\DTO\Contracts\DTOInterface;

/**
 * Abstract base class for all DTOs
 * Provides common validation utilities
 */
abstract class BaseDTO implements DTOInterface
{
    /**
     * Common validation utilities
     */
    protected function validateEmail(string $email, string $fieldName = 'email'): ?string
    {
        if (empty($email)) {
            return "{$fieldName} is required";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid {$fieldName} format";
        }
        
        return null;
    }

    protected function validateRequired(mixed $value, string $fieldName): ?string
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            return "{$fieldName} is required";
        }
        return null;
    }

    protected function validateMinLength(string $value, int $minLength, string $fieldName): ?string
    {
        if (strlen($value) < $minLength) {
            return "{$fieldName} must be at least {$minLength} characters long";
        }
        return null;
    }

    protected function validateMaxLength(string $value, int $maxLength, string $fieldName): ?string
    {
        if (strlen($value) > $maxLength) {
            return "{$fieldName} must not exceed {$maxLength} characters";
        }
        return null;
    }

    protected function validateNumericRange(int $value, int $min, int $max, string $fieldName): ?string
    {
        if ($value < $min || $value > $max) {
            return "{$fieldName} must be between {$min} and {$max}";
        }
        return null;
    }

    protected function validateInArray(mixed $value, array $allowedValues, string $fieldName): ?string
    {
        if (!in_array($value, $allowedValues, true)) {
            return "{$fieldName} must be one of: " . implode(', ', $allowedValues);
        }
        return null;
    }

    protected function validateDate(string $date, string $fieldName, string $format = 'Y-m-d'): ?string
    {
        if (empty($date)) {
            return "{$fieldName} is required";
        }

        $dateTime = \DateTime::createFromFormat($format, $date);
        if (!$dateTime || $dateTime->format($format) !== $date) {
            return "Invalid {$fieldName} format (expected: {$format})";
        }

        return null;
    }

    protected function validateFutureDate(string $date, string $fieldName): ?string
    {
        $dateError = $this->validateDate($date, $fieldName);
        if ($dateError) return $dateError;

        if (new \DateTime($date) < new \DateTime('today')) {
            return "{$fieldName} cannot be in the past";
        }

        return null;
    }

    protected function validatePhone(?string $phone, string $fieldName = 'phone'): ?string
    {
        if ($phone === null || $phone === '') {
            return null; // Optional field
        }

        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            return "Invalid {$fieldName} format";
        }

        return null;
    }

    /**
     * Collect all validation errors from validation methods
     */
    protected function collectErrors(array $validationMethods): array
    {
        $errors = [];
        
        foreach ($validationMethods as $method) {
            $error = $method();
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }
}
