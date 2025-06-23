<?php

namespace App\DTO\Room;

class RoomDTO
{
    public int $id;
    public string $number;
    public int $room_type_id;
    public int $floor;
    public string $status;
    public ?string $room_type_name;
    public ?int $capacity;
    public ?float $price_night;
    public ?string $location;
      public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id = (int)($data['id'] ?? 0);
        $dto->number = $data['number'] ?? '';
        $dto->room_type_id = (int)($data['room_type_id'] ?? 0);
        $dto->floor = (int)($data['floor'] ?? 0);
        $dto->status = $data['status'] ?? 'available';
        $dto->room_type_name = $data['room_type_name'] ?? null;
        $dto->capacity = isset($data['capacity']) ? (int)$data['capacity'] : null;
        $dto->price_night = isset($data['price_night']) ? (float)$data['price_night'] : null;
        $dto->location = $data['location'] ?? null;
        return $dto;
    }
      public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'floor' => $this->floor,
            'status' => $this->status,
            'room_type' => [
                'id' => $this->room_type_id,
                'name' => $this->room_type_name,
                'capacity' => $this->capacity,
                'price_night' => $this->price_night,
                'location' => $this->location
            ]
        ];
    }
}
