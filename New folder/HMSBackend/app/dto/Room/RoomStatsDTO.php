<?php

namespace App\DTO\Room;

use App\DTO\BaseDTO;

class RoomStatsDTO extends BaseDTO
{
    public int $total_rooms;
    public int $available_rooms;
    public int $occupied_rooms;
    public int $maintenance_rooms;
    public int $out_of_order_rooms;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->total_rooms = $data['total'] ?? 0;
        $dto->available_rooms = $data['available'] ?? 0;
        $dto->occupied_rooms = $data['occupied'] ?? 0;
        $dto->maintenance_rooms = $data['maintenance'] ?? 0;
        $dto->out_of_order_rooms = $data['out_of_order'] ?? 0;
        return $dto;
    }

    public function validate(): array
    {
        return []; // Stats DTOs don't need validation
    }

    public function toArray(): array
    {
        return [
            'total_rooms' => $this->total_rooms,
            'available_rooms' => $this->available_rooms,
            'occupied_rooms' => $this->occupied_rooms,
            'maintenance_rooms' => $this->maintenance_rooms,
            'out_of_order_rooms' => $this->out_of_order_rooms,
        ];
    }
}
