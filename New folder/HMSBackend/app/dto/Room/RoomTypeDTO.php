<?php

namespace App\DTO\Room;

use App\DTO\BaseDTO;

class RoomTypeDTO extends BaseDTO
{
    public int $id;
    public string $name;
    public int $capacity;
    public float $price_night;
    public string $location;
    
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id = (int)($data['id'] ?? 0);
        $dto->name = $data['name'] ?? '';
        $dto->capacity = (int)($data['capacity'] ?? 0);
        $dto->price_night = (float)($data['price_night'] ?? 0);
        $dto->location = $data['location'] ?? '';
        return $dto;
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateRequired($this->name, 'name'),
            fn() => $this->validateNumericRange($this->capacity, 1, 4, 'capacity'),
            fn() => $this->validateInArray($this->location, ['cabin', 'hotel'], 'location')
        ]);
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'price_night' => $this->price_night,
            'location' => $this->location
        ];
    }
}
