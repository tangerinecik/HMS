<?php

namespace App\DTO\Room;

class RoomTypeCreateDTO
{
    public string $name;
    public int $capacity;
    public float $price_night;
    public string $location;
    
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? '';
        $dto->capacity = (int)($data['capacity'] ?? 0);
        $dto->price_night = (float)($data['price_night'] ?? 0);
        $dto->location = $data['location'] ?? '';
        return $dto;
    }
    
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'capacity' => $this->capacity,
            'price_night' => $this->price_night,
            'location' => $this->location
        ];
    }
    
    public function validate(): array
    {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = "Name is required";
        }
        
        if ($this->capacity < 1 || $this->capacity > 4) {
            $errors[] = "Capacity must be between 1 and 4";
        }
        
        if ($this->price_night <= 0) {
            $errors[] = "Price per night must be greater than 0";
        }
        
        if (!in_array($this->location, ['cabin', 'hotel'])) {
            $errors[] = "Location must be either 'cabin' or 'hotel'";
        }
        
        return $errors;
    }
}
