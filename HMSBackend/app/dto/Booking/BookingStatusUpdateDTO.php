<?php

namespace App\DTO\Booking;

class BookingStatusUpdateDTO
{
    public string $status;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->status = $data['status'] ?? '';
        
        return $dto;
    }

    public function validate(): array
    {
        $errors = [];
        $validStatuses = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];

        if (empty($this->status)) {
            $errors[] = 'Status is required';
        } elseif (!in_array($this->status, $validStatuses)) {
            $errors[] = 'Invalid status. Valid statuses are: ' . implode(', ', $validStatuses);
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status
        ];
    }
}
