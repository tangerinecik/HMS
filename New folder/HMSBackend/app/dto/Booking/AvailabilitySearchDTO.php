<?php

namespace App\DTO\Booking;

use App\DTO\BaseDTO;

class AvailabilitySearchDTO extends BaseDTO
{
    public string $checkIn;
    public string $checkOut;
    public int $guests;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->checkIn = $data['check_in'] ?? '';
        $dto->checkOut = $data['check_out'] ?? '';
        $dto->guests = (int)($data['guests'] ?? 1);
        return $dto;
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateFutureDate($this->checkIn, 'check-in date'),
            fn() => $this->validateDate($this->checkOut, 'check-out date'),
            fn() => $this->validateDateOrder(),
            fn() => $this->validateNumericRange($this->guests, 1, 4, 'guests')
        ]);
    }

    public function toArray(): array
    {
        return [
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'guests' => $this->guests
        ];
    }

    private function validateDateOrder(): ?string
    {
        if (!empty($this->checkIn) && !empty($this->checkOut)) {
            if (new \DateTime($this->checkIn) >= new \DateTime($this->checkOut)) {
                return 'Check-out date must be after check-in date';
            }
        }
        return null;
    }
}
