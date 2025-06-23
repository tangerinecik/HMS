<?php

namespace App\DTO\Room;

class RoomCreateDTO
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
      public function toArray(): array
    {
        return [
            'number' => $this->number,
            'room_type_id' => $this->room_type_id,
            'floor' => $this->floor,
            'status' => $this->status
        ];
    }
    
    public function validate(): array
    {
        $errors = [];
        
        if (empty($this->number)) {
            $errors[] = "Room number is required";
        }
        
        if ($this->room_type_id <= 0) {
            $errors[] = "Valid room type ID is required";
        }
        
        return $errors;
    }
}
