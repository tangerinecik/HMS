<?php

namespace App\DTO\Booking;

class AvailabilitySearchDTO
{
    public string $checkIn;
    public string $checkOut;
    public int $guests;

    public function __construct(array $data)
    {
        $this->checkIn = $data['check_in'] ?? '';
        $this->checkOut = $data['check_out'] ?? '';
        $this->guests = (int)($data['guests'] ?? 1);
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->checkIn)) {
            $errors[] = 'Check-in date is required';
        } elseif (!$this->isValidDate($this->checkIn)) {
            $errors[] = 'Invalid check-in date format (YYYY-MM-DD required)';
        } elseif (new \DateTime($this->checkIn) < new \DateTime('today')) {
            $errors[] = 'Check-in date cannot be in the past';
        }

        if (empty($this->checkOut)) {
            $errors[] = 'Check-out date is required';
        } elseif (!$this->isValidDate($this->checkOut)) {
            $errors[] = 'Invalid check-out date format (YYYY-MM-DD required)';
        }

        if (!empty($this->checkIn) && !empty($this->checkOut)) {
            if (new \DateTime($this->checkIn) >= new \DateTime($this->checkOut)) {
                $errors[] = 'Check-out date must be after check-in date';
            }
        }

        if ($this->guests < 1 || $this->guests > 4) {
            $errors[] = 'Number of guests must be between 1 and 4';
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
