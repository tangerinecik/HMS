<?php

namespace App\DTO\Room;

class RoomStatsDTO
{
    public int $total_rooms;
    public int $available_rooms;
    public int $occupied_rooms;
    public int $maintenance_rooms;
    public int $out_of_order_rooms;

    public function __construct(array $data)
    {
        $this->total_rooms = $data['total'] ?? 0;
        $this->available_rooms = $data['available'] ?? 0;
        $this->occupied_rooms = $data['occupied'] ?? 0;
        $this->maintenance_rooms = $data['maintenance'] ?? 0;
        $this->out_of_order_rooms = $data['out_of_order'] ?? 0;
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
