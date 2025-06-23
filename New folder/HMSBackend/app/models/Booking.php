<?php

namespace App\Models;

use PDOException;

class Booking extends Model
{
    /**
     * Get all bookings with pagination and optional filters
     */
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $whereConditions = [];
        $params = [];
        
        // Build WHERE conditions based on filters
        if (!empty($filters['customer_id'])) {
            $whereConditions[] = "b.customer_id = :customer_id";
            $params['customer_id'] = $filters['customer_id'];
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "b.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['room_id'])) {
            $whereConditions[] = "b.room_id = :room_id";
            $params['room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['check_in_from'])) {
            $whereConditions[] = "b.check_in >= :check_in_from";
            $params['check_in_from'] = $filters['check_in_from'];
        }
        
        if (!empty($filters['check_in_to'])) {
            $whereConditions[] = "b.check_in <= :check_in_to";
            $params['check_in_to'] = $filters['check_in_to'];
        }
        
        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
        
        $stmt = self::$pdo->prepare("
            SELECT b.id, b.ref_code, b.customer_id, b.room_id, b.status,
                   b.check_in, b.check_out, b.guests, b.nights, b.total_amount,
                   b.special_requests, b.created_at, b.updated_at,
                   r.number AS room_number, r.floor,
                   rt.name AS room_type_name, rt.capacity AS room_capacity,
                   rt.price_night, rt.location,
                   u.first_name, u.last_name, u.email, u.phone
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id
            JOIN users u ON b.customer_id = u.id
            {$whereClause}
            ORDER BY b.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
          // Bind filter parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        
        try {
            error_log("Executing getAll query with params: " . json_encode($params));
            error_log("SQL: " . $stmt->queryString);
            $stmt->execute();
            
            $result = $stmt->fetchAll();
            error_log("Query returned " . count($result) . " bookings");
            return $result;
        } catch (\PDOException $e) {
            error_log("Database error in getAll: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw $e;
        }
    }
    
    /**
     * Get total count of bookings with optional filters
     */
    public function getTotalCount(array $filters = []): int
    {
        $whereConditions = [];
        $params = [];
        
        // Build WHERE conditions based on filters (same as getAll)
        if (!empty($filters['customer_id'])) {
            $whereConditions[] = "customer_id = :customer_id";
            $params['customer_id'] = $filters['customer_id'];
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['room_id'])) {
            $whereConditions[] = "room_id = :room_id";
            $params['room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['check_in_from'])) {
            $whereConditions[] = "check_in >= :check_in_from";
            $params['check_in_from'] = $filters['check_in_from'];
        }
        
        if (!empty($filters['check_in_to'])) {
            $whereConditions[] = "check_in <= :check_in_to";
            $params['check_in_to'] = $filters['check_in_to'];
        }
          $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
        
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM bookings {$whereClause}");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        try {
            error_log("Executing getTotalCount query with params: " . json_encode($params));
            error_log("SQL: " . $stmt->queryString);
            $stmt->execute();
            
            $count = (int) $stmt->fetchColumn();
            error_log("Total count query returned: $count");
            return $count;
        } catch (\PDOException $e) {
            error_log("Database error in getTotalCount: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw $e;
        }
    }
    
    /**
     * Get booking by ID with full details
     */
    public function getById(int $id): ?array
    {
        $stmt = self::$pdo->prepare("
            SELECT b.id, b.ref_code, b.customer_id, b.room_id, b.status,
                   b.check_in, b.check_out, b.guests, b.nights, b.total_amount,
                   b.special_requests, b.created_at, b.updated_at,
                   r.number AS room_number, r.floor,
                   rt.name AS room_type_name, rt.capacity AS room_capacity,
                   rt.price_night, rt.location,
                   u.first_name, u.last_name, u.email, u.phone
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id
            JOIN users u ON b.customer_id = u.id
            WHERE b.id = :id
        ");
        
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get booking by reference code
     */
    public function getByRefCode(string $refCode): ?array
    {
        $stmt = self::$pdo->prepare("
            SELECT b.id, b.ref_code, b.customer_id, b.room_id, b.status,
                   b.check_in, b.check_out, b.guests, b.nights, b.total_amount,
                   b.special_requests, b.created_at, b.updated_at,
                   r.number AS room_number, r.floor,
                   rt.name AS room_type_name, rt.capacity AS room_capacity,
                   rt.price_night, rt.location,
                   u.first_name, u.last_name, u.email, u.phone
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id
            JOIN users u ON b.customer_id = u.id
            WHERE b.ref_code = :ref_code
        ");
        
        $stmt->bindParam(':ref_code', $refCode);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Create a new booking
     */
    public function create(array $data): int
    {
        try {
            self::$pdo->beginTransaction();
            
            // Calculate nights and total amount
            $checkIn = new \DateTime($data['check_in']);
            $checkOut = new \DateTime($data['check_out']);
            $nights = $checkIn->diff($checkOut)->days;
            
            // Get room price
            $roomStmt = self::$pdo->prepare("
                SELECT rt.price_night 
                FROM rooms r 
                JOIN room_types rt ON r.room_type_id = rt.id 
                WHERE r.id = :room_id
            ");
            $roomStmt->bindParam(':room_id', $data['room_id'], \PDO::PARAM_INT);
            $roomStmt->execute();
            $roomData = $roomStmt->fetch();
            
            if (!$roomData) {
                throw new \InvalidArgumentException('Room not found');
            }
            
            $totalAmount = $nights * $roomData['price_night'];            // Insert booking WITHOUT the ref_code field to avoid trigger issues
            // We'll update the ref_code in a separate query
            $stmt = self::$pdo->prepare("
                INSERT INTO bookings (customer_id, room_id, status, check_in, check_out, 
                                    guests, nights, total_amount, special_requests)
                VALUES (:customer_id, :room_id, :status, :check_in, :check_out, 
                        :guests, :nights, :total_amount, :special_requests)
            ");
            
            $status = $data['status'] ?? 'confirmed';
            $specialRequests = $data['special_requests'] ?? null;
            $stmt->bindParam(':customer_id', $data['customer_id'], \PDO::PARAM_INT);
            $stmt->bindParam(':room_id', $data['room_id'], \PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':check_in', $data['check_in']);
            $stmt->bindParam(':check_out', $data['check_out']);
            $stmt->bindParam(':guests', $data['guests'], \PDO::PARAM_INT);
            $stmt->bindParam(':nights', $nights, \PDO::PARAM_INT);
            $stmt->bindParam(':total_amount', $totalAmount);
            $stmt->bindParam(':special_requests', $specialRequests);
              $stmt->execute();
            $bookingId = (int) self::$pdo->lastInsertId();
            
            // Generate reference code based on booking ID - safer and guaranteed unique
            $refCode = 'B' . str_pad($bookingId, 9, '0', STR_PAD_LEFT);
            
            // Update the booking with the reference code in a separate query
            $updateStmt = self::$pdo->prepare("UPDATE bookings SET ref_code = :ref_code WHERE id = :id");
            $updateStmt->bindParam(':ref_code', $refCode);
            $updateStmt->bindParam(':id', $bookingId, \PDO::PARAM_INT);
            $updateStmt->execute();
            
            // Update room status to occupied if booking is confirmed
            if ($status === 'confirmed') {
                $this->updateRoomStatus($data['room_id'], 'occupied');
            }
            
            self::$pdo->commit();
            return $bookingId;        } catch (PDOException $e) {
            self::$pdo->rollBack();
            
            // Log the error with details for debugging
            error_log('Database error in booking creation: ' . $e->getMessage());
            error_log('SQL State: ' . $e->getCode());
            error_log('Booking data: ' . json_encode($data));
            
            // Handle specific database constraint violations
            if (strpos($e->getMessage(), 'Room already booked') !== false) {
                throw new \InvalidArgumentException('Room is not available for the selected dates');
            }
            
            // Handle ref_code constraint issues
            if (strpos($e->getMessage(), "field 'ref_code'") !== false) {
                error_log('Field ref_code error detected. Trying again with a different reference code.');
                // Try again with a different reference code (implement retry logic if needed)
                throw new \InvalidArgumentException('Problem generating booking reference. Please try again.');
            }
            
            // Check for other common errors
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                if (strpos($e->getMessage(), 'customer_id') !== false) {
                    throw new \InvalidArgumentException('Invalid customer ID');
                }
                if (strpos($e->getMessage(), 'room_id') !== false) {
                    throw new \InvalidArgumentException('Invalid room ID');
                }
                throw new \InvalidArgumentException('Data validation failed');
            }
            
            throw $e;
        } catch (\Exception $e) {
            // Handle any other exceptions
            if (isset(self::$pdo) && self::$pdo->inTransaction()) {
                self::$pdo->rollBack();
            }
            
            error_log('General error in booking creation: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update booking status
     */
    public function updateStatus(int $id, string $status): bool
    {
        try {
            self::$pdo->beginTransaction();
            
            // Get current booking data
            $booking = $this->getById($id);
            if (!$booking) {
                return false;
            }
            
            $stmt = self::$pdo->prepare("
                UPDATE bookings 
                SET status = :status, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            // Update room status based on booking status
            $this->updateRoomStatusByBookingStatus($booking['room_id'], $status);
            
            self::$pdo->commit();
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            self::$pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Cancel a booking
     */
    public function cancel(int $id): bool
    {
        return $this->updateStatus($id, 'cancelled');
    }
    
    /**
     * Check room availability for given dates
     */
    public function checkRoomAvailability(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        $sql = "
            SELECT COUNT(*) FROM bookings 
            WHERE room_id = :room_id 
            AND status NOT IN ('cancelled') 
            AND :check_in < check_out 
            AND :check_out > check_in
        ";
        
        $params = [
            'room_id' => $roomId,
            'check_in' => $checkIn,
            'check_out' => $checkOut
        ];
        
        if ($excludeBookingId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeBookingId;
        }
        
        $stmt = self::$pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn() === 0;
    }
    
    /**
     * Get available rooms for given dates and guest count
     */
    public function getAvailableRooms(string $checkIn, string $checkOut, int $guests): array
    {
        $stmt = self::$pdo->prepare("
            SELECT r.id, r.number, r.floor, r.status,
                   rt.id AS room_type_id, rt.name AS room_type_name, 
                   rt.capacity, rt.price_night, rt.location
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE rt.capacity >= :guests
            AND r.status = 'available'
            AND r.id NOT IN (
                SELECT DISTINCT room_id FROM bookings 
                WHERE status NOT IN ('cancelled')
                AND :check_in < check_out 
                AND :check_out > check_in
            )
            ORDER BY rt.price_night ASC, r.number ASC
        ");
        
        $stmt->bindParam(':guests', $guests, \PDO::PARAM_INT);
        $stmt->bindParam(':check_in', $checkIn);
        $stmt->bindParam(':check_out', $checkOut);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get booking statistics
     */
    public function getBookingStats(): array
    {
        $stmt = self::$pdo->query("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as total_revenue
            FROM bookings 
            GROUP BY status
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update room status based on booking status
     */
    private function updateRoomStatusByBookingStatus(int $roomId, string $bookingStatus): void
    {
        $roomStatus = match($bookingStatus) {
            'confirmed' => 'occupied',
            'checked_in' => 'occupied',
            'checked_out' => 'cleaning',
            'cancelled' => 'available',
            default => null
        };
        
        if ($roomStatus) {
            $this->updateRoomStatus($roomId, $roomStatus);
        }
    }
    
    /**
     * Update room status
     */
    private function updateRoomStatus(int $roomId, string $status): void
    {
        $stmt = self::$pdo->prepare("UPDATE rooms SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $roomId, \PDO::PARAM_INT);
        $stmt->execute();
    }
}
