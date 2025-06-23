<?php

namespace App\Models;

class RoomType extends Model
{
    public function getAll(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $stmt = self::$pdo->prepare("
            SELECT id, name, capacity, price_night, location
            FROM room_types
            ORDER BY id
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getTotalCount(): int
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) FROM room_types");
        return (int) $stmt->fetchColumn();
    }
    
    public function getById(int $id): ?array
    {
        $stmt = self::$pdo->prepare("
            SELECT id, name, capacity, price_night, location
            FROM room_types
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    public function create(array $data): int
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO room_types (name, capacity, price_night, location)
            VALUES (:name, :capacity, :price_night, :location)
        ");
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':capacity', $data['capacity'], \PDO::PARAM_INT);
        $stmt->bindParam(':price_night', $data['price_night']);
        $stmt->bindParam(':location', $data['location']);
        
        $stmt->execute();
        
        return (int) self::$pdo->lastInsertId();
    }
    
    public function update(int $id, array $data): bool
    {
        $stmt = self::$pdo->prepare("
            UPDATE room_types
            SET name = :name,
                capacity = :capacity,
                price_night = :price_night,
                location = :location
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':capacity', $data['capacity'], \PDO::PARAM_INT);
        $stmt->bindParam(':price_night', $data['price_night']);
        $stmt->bindParam(':location', $data['location']);
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    public function delete(int $id): bool
    {
        // Check if there are any rooms using this room type
        $checkStmt = self::$pdo->prepare("
            SELECT COUNT(*) FROM rooms WHERE room_type_id = :id
        ");
        $checkStmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ((int) $checkStmt->fetchColumn() > 0) {
            return false; // Can't delete, rooms are using this type
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM room_types WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
