<?php

namespace App\DTO\Booking;

class BookingDTO
{
    public int $id;
    public string $ref_code;
    public int $customer_id;
    public int $room_id;
    public string $status;
    public string $check_in;
    public string $check_out;
    public int $guests;
    public float $total_amount;
    public int $nights;
    public ?string $special_requests;
    public string $created_at;
    public string $updated_at;

    // Room details
    public ?string $room_number;
    public ?int $floor;
    public ?string $room_type_name;
    public ?int $room_capacity;
    public ?float $price_night;
    public ?string $location;

    // Customer details
    public ?string $first_name;
    public ?string $last_name;
    public ?string $email;
    public ?string $phone;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        
        // Booking fields
        $dto->id = (int) $data['id'];
        $dto->ref_code = $data['ref_code'];
        $dto->customer_id = (int) $data['customer_id'];
        $dto->room_id = (int) $data['room_id'];
        $dto->status = $data['status'];
        $dto->check_in = $data['check_in'];
        $dto->check_out = $data['check_out'];
        $dto->guests = (int) $data['guests'];
        $dto->total_amount = (float) $data['total_amount'];
        $dto->nights = (int) $data['nights'];
        $dto->special_requests = $data['special_requests'] ?? null;
        $dto->created_at = $data['created_at'];
        $dto->updated_at = $data['updated_at'];

        // Room details (if joined)
        $dto->room_number = $data['room_number'] ?? null;
        $dto->floor = isset($data['floor']) ? (int) $data['floor'] : null;
        $dto->room_type_name = $data['room_type_name'] ?? null;
        $dto->room_capacity = isset($data['room_capacity']) ? (int) $data['room_capacity'] : null;
        $dto->price_night = isset($data['price_night']) ? (float) $data['price_night'] : null;
        $dto->location = $data['location'] ?? null;

        // Customer details (if joined)
        $dto->first_name = $data['first_name'] ?? null;
        $dto->last_name = $data['last_name'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->phone = $data['phone'] ?? null;

        return $dto;
    }

    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'ref_code' => $this->ref_code,
            'customer_id' => $this->customer_id,
            'room_id' => $this->room_id,
            'status' => $this->status,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'guests' => $this->guests,
            'total_amount' => $this->total_amount,
            'nights' => $this->nights,
            'special_requests' => $this->special_requests,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status_display' => $this->getStatusDisplay()
        ];

        // Add room details if available
        if ($this->room_number !== null) {
            $result['room'] = [
                'number' => $this->room_number,
                'floor' => $this->floor,
                'type_name' => $this->room_type_name,
                'capacity' => $this->room_capacity,
                'price_night' => $this->price_night,
                'location' => $this->location
            ];
        }

        // Add customer details if available
        if ($this->first_name !== null) {
            $result['customer'] = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone
            ];
        }

        return $result;
    }

    public function getStatusDisplay(): string
    {
        return match($this->status) {
            'confirmed' => 'Confirmed',
            'checked_in' => 'Checked In',
            'checked_out' => 'Checked Out',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status)
        };
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['checked_in', 'checked_out', 'cancelled']);
    }

    public function isActive(): bool
    {
        return !in_array($this->status, ['cancelled', 'checked_out']);
    }

    public function canCheckIn(): bool
    {
        $today = new \DateTime('today');
        $checkInDate = new \DateTime($this->check_in);
        
        return $this->status === 'confirmed' && $checkInDate <= $today;
    }

    public function canCheckOut(): bool
    {
        return $this->status === 'checked_in';
    }
}
