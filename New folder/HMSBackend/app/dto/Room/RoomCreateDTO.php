<?php

namespace App\DTO\Room;

use App\DTO\BaseDTO;

class RoomCreateDTO extends BaseDTO
{
    public string $number;
    public int $room_type_id;
    public int $floor;
    public string $status;
    
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->number = $data['number'] ?? '';
        $dto->room_type_id = (int)($data['room_type_id'] ?? 0);
        $dto->floor = (int)($data['floor'] ?? 0);
        $dto->status = $data['status'] ?? 'available';
        return $dto;
    }
      
    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateRequired($this->number, 'room number'),
            fn() => $this->validateNumericRange($this->room_type_id, 1, 9999, 'room type ID'),
            fn() => $this->validateInArray($this->status, ['available', 'occupied', 'cleaning', 'maintenance', 'out_of_order'], 'status')
        ]);
    }

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'room_type_id' => $this->room_type_id,
            'floor' => $this->floor,
            'status' => $this->status
        ];
    }
}
