<?php

namespace App\DTO\Booking;

class BookingCreateDTO
{
    public int $customer_id;
    public int $room_id;
    public string $check_in;
    public string $check_out;
    public int $guests;
    public ?string $special_requests;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->customer_id = (int) $data['customer_id'];
        $dto->room_id = (int) $data['room_id'];
        $dto->check_in = $data['check_in'];
        $dto->check_out = $data['check_out'];
        $dto->guests = (int) $data['guests'];
        $dto->special_requests = $data['special_requests'] ?? null;
        
        return $dto;
    }

    public function validate(): array
    {
        $errors = [];

        // Validate customer_id
        if ($this->customer_id <= 0) {
            $errors[] = 'Valid customer ID is required';
        }

        // Validate room_id
        if ($this->room_id <= 0) {
            $errors[] = 'Valid room ID is required';
        }

        // Validate check_in date
        if (empty($this->check_in)) {
            $errors[] = 'Check-in date is required';
        } elseif (!$this->isValidDate($this->check_in)) {
            $errors[] = 'Invalid check-in date format (YYYY-MM-DD required)';
        } elseif (new \DateTime($this->check_in) < new \DateTime('today')) {
            $errors[] = 'Check-in date cannot be in the past';
        }

        // Validate check_out date
        if (empty($this->check_out)) {
            $errors[] = 'Check-out date is required';
        } elseif (!$this->isValidDate($this->check_out)) {
            $errors[] = 'Invalid check-out date format (YYYY-MM-DD required)';
        }

        // Validate date relationship
        if (!empty($this->check_in) && !empty($this->check_out)) {
            $checkInDate = new \DateTime($this->check_in);
            $checkOutDate = new \DateTime($this->check_out);
            
            if ($checkInDate >= $checkOutDate) {
                $errors[] = 'Check-out date must be after check-in date';
            }
            
            // Ensure reasonable booking length (max 30 days)
            $daysDiff = $checkInDate->diff($checkOutDate)->days;
            if ($daysDiff > 30) {
                $errors[] = 'Booking cannot exceed 30 days';
            }
        }

        // Validate guests
        if ($this->guests < 1 || $this->guests > 4) {
            $errors[] = 'Number of guests must be between 1 and 4';
        }

        // Validate special requests length
        if ($this->special_requests && strlen($this->special_requests) > 500) {
            $errors[] = 'Special requests cannot exceed 500 characters';
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'room_id' => $this->room_id,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'guests' => $this->guests,
            'special_requests' => $this->special_requests
        ];
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
