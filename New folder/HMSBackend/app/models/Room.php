<?php

namespace App\Models;

class Room extends Model
{    public function getAll(int $page = 1, int $limit = 10, ?int $roomTypeId = null): array
    {
        $offset = ($page - 1) * $limit;
        
        $whereClause = $roomTypeId ? 'WHERE r.room_type_id = :room_type_id' : '';
        
        $sql = "
            SELECT r.id, r.number, r.floor, r.status, r.room_type_id,
                   rt.name AS room_type_name, rt.capacity, rt.price_night, rt.location
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            {$whereClause}
            ORDER BY r.id
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = self::$pdo->prepare($sql);
        
        if ($roomTypeId) {
            $stmt->bindParam(':room_type_id', $roomTypeId, \PDO::PARAM_INT);
        }
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getTotalCount(): int
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) FROM rooms");
        return (int) $stmt->fetchColumn();
    }
    
    public function getById(int $id): ?array
    {        $stmt = self::$pdo->prepare("
            SELECT r.id, r.number, r.floor, r.status, r.room_type_id,
                   rt.name AS room_type_name, rt.capacity, rt.price_night, rt.location
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.id = :id
        ");
        
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result ?: null;
    }    public function create(array $data): int
    {
        $status = $data['status'] ?? 'available';
        
        $stmt = self::$pdo->prepare("
            INSERT INTO rooms (number, room_type_id, floor, status)
            VALUES (:number, :room_type_id, :floor, :status)
        ");
        
        $stmt->bindParam(':number', $data['number']);
        $stmt->bindParam(':room_type_id', $data['room_type_id'], \PDO::PARAM_INT);
        $stmt->bindParam(':floor', $data['floor'], \PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        
        $stmt->execute();
        
        return (int) self::$pdo->lastInsertId();
    }
      public function update(int $id, array $data): bool
    {
        $stmt = self::$pdo->prepare("
            UPDATE rooms
            SET number = :number,
                room_type_id = :room_type_id,
                floor = :floor,
                status = :status
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->bindParam(':number', $data['number']);
        $stmt->bindParam(':room_type_id', $data['room_type_id'], \PDO::PARAM_INT);
        $stmt->bindParam(':floor', $data['floor'], \PDO::PARAM_INT);
        $stmt->bindParam(':status', $data['status']);
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    public function delete(int $id): bool
    {
        // Check if there are any bookings for this room
        // This would require a bookings table - for now, we'll skip this check
        
        $stmt = self::$pdo->prepare("DELETE FROM rooms WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    public function getRoomsByType(int $roomTypeId, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
          $stmt = self::$pdo->prepare("
            SELECT r.id, r.number, r.floor, r.status, r.room_type_id,
                   rt.name AS room_type_name, rt.capacity, rt.price_night, rt.location
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.room_type_id = :room_type_id
            ORDER BY r.id
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':room_type_id', $roomTypeId, \PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getTotalCountByType(int $roomTypeId): int
    {
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = :room_type_id");
        $stmt->bindParam(':room_type_id', $roomTypeId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    
    public function getTotalCountByStatus(string $status): int
    {
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM rooms WHERE status = :status");
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    
    public function getByStatus(string $status, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $stmt = self::$pdo->prepare("
            SELECT r.id, r.number, r.floor, r.status, r.room_type_id,
                   rt.name AS room_type_name, rt.capacity, rt.price_night, rt.location
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.status = :status
            ORDER BY r.id
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = self::$pdo->prepare("UPDATE rooms SET status = :status WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    public function getStatusCounts(): array
    {
        $stmt = self::$pdo->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM rooms 
            GROUP BY status
        ");
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Initialize with zero counts for all statuses
        $counts = [
            'available' => 0,
            'occupied' => 0,
            'cleaning' => 0,
            'maintenance' => 0,
            'out_of_order' => 0,
            'total' => 0
        ];
        
        // Fill in actual counts
        foreach ($results as $result) {
            $counts[$result['status']] = (int)$result['count'];
            $counts['total'] += (int)$result['count'];
        }
        
        return $counts;
    }

    /**
     * Check room availability for a specific date range and room type
     * 
     * @param string $checkIn Check-in date (YYYY-MM-DD)
     * @param string $checkOut Check-out date (YYYY-MM-DD)
     * @param int|null $roomTypeId Optional room type ID to filter by
     * @return array Array containing 'available' and 'total' room counts
     */
    public function checkAvailabilityForDates(string $checkIn, string $checkOut, ?int $roomTypeId = null): array
    {
        // Basic validation
        if (empty($checkIn) || empty($checkOut)) {
            throw new \InvalidArgumentException('Check-in and check-out dates are required');
        }

        $checkInDate = new \DateTime($checkIn);
        $checkOutDate = new \DateTime($checkOut);
        
        if ($checkInDate >= $checkOutDate) {
            throw new \InvalidArgumentException('Check-out date must be after check-in date');
        }        // For now, we'll use a simple availability check based on room status
        // In a real system, this would check against actual bookings/reservations
        $query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' AND r.id NOT IN (
                    SELECT DISTINCT room_id 
                    FROM bookings 
                    WHERE status NOT IN ('cancelled', 'payment_failed')
                      AND :check_in < check_out
                      AND :check_out > check_in
                ) THEN 1 ELSE 0 END) as available
            FROM rooms r
        ";
        
        $params = [
            'check_in' => $checkIn,
            'check_out' => $checkOut
        ];
          if ($roomTypeId !== null) {
            $query .= " WHERE r.room_type_id = :room_type_id";
            $params['room_type_id'] = $roomTypeId;
        }
        
        $stmt = self::$pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            if ($key === 'room_type_id') {
                $stmt->bindParam(':' . $key, $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindParam(':' . $key, $value, \PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return [
            'available' => (int) ($result['available'] ?? 0),
            'total' => (int) ($result['total'] ?? 0),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'room_type_id' => $roomTypeId
        ];
    }
}
