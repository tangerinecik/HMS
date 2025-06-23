<?php

namespace App\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\DTO\Room\RoomDTO;
use App\DTO\Room\RoomCreateDTO;
use App\Services\LegacyResponseAdapter as ResponseService;

class RoomController extends Controller
{
    private Room $roomModel;
    private RoomType $roomTypeModel;
    private const VALID_STATUSES = ['available', 'occupied', 'cleaning', 'maintenance', 'out_of_order'];
    
    public function __construct()
    {
        parent::__construct();
        $this->roomModel = new Room();
        $this->roomTypeModel = new RoomType();
    }

    /**
     * Get all rooms with pagination and optional filters
     */
    public function getAllRooms(): void
    {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
            $roomTypeId = !empty($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : null;
            
            $rooms = $this->roomModel->getAll($page, $limit, $roomTypeId);
            $totalCount = $roomTypeId ? $this->roomModel->getTotalCountByType($roomTypeId) : $this->roomModel->getTotalCount();
            
            $roomDTOs = $this->mapToDTOs($rooms, RoomDTO::class);
            $this->sendPaginatedResponse($roomDTOs, $page, $limit, $totalCount, 'data');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve rooms', $e);
        }
    }

    /**
     * Get a specific room by ID
     */
    public function getRoom(int $id): void
    {
        try {
            $room = $this->roomModel->getById($id);
            if (!$room) {
                $this->handleNotFound('Room');
                return;
            }
            
            ResponseService::Send(RoomDTO::fromArray($room)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve room', $e);
        }
    }

    /**
     * Create a new room
     */
    public function createRoom(): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $data = $this->getRequestData();
            if (!$data) return;
            
            $roomDTO = RoomCreateDTO::fromArray($data);
            if ($this->handleValidationErrors($roomDTO->validate())) return;
            
            // Validate room type exists
            if (!$this->validateRoomTypeExists($roomDTO->room_type_id)) {
                ResponseService::Error('Invalid room type ID', 400);
                return;
            }
            
            $id = $this->roomModel->create($roomDTO->toArray());
            $room = $this->roomModel->getById($id);
            
            ResponseService::Send(RoomDTO::fromArray($room)->toArray(), 201);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to create room', $e);
        }
    }

    /**
     * Update a room
     */
    public function updateRoom(int $id): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $data = $this->getRequestData();
            if (!$data) return;
            
            $existingRoom = $this->roomModel->getById($id);
            if (!$existingRoom) {
                $this->handleNotFound('Room');
                return;
            }
            
            $roomDTO = RoomCreateDTO::fromArray($data);
            if ($this->handleValidationErrors($roomDTO->validate())) return;
            
            // Validate room type exists if provided
            if (isset($data['room_type_id']) && !$this->validateRoomTypeExists($roomDTO->room_type_id)) {
                ResponseService::Error('Invalid room type ID', 400);
                return;
            }
            
            $success = $this->roomModel->update($id, $roomDTO->toArray());
            if (!$success) {
                ResponseService::Error('Failed to update room', 500);
                return;
            }
            
            $updatedRoom = $this->roomModel->getById($id);
            ResponseService::Send(RoomDTO::fromArray($updatedRoom)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update room', $e);
        }
    }
    
    /**
     * Delete a room
     */
    public function deleteRoom(int $id): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $existingRoom = $this->roomModel->getById($id);
            if (!$existingRoom) {
                $this->handleNotFound('Room');
                return;
            }
            
            $success = $this->roomModel->delete($id);
            if (!$success) {
                ResponseService::Error('Cannot delete room - it may have active bookings', 400);
                return;
            }
            
            ResponseService::Send(['message' => 'Room deleted successfully']);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to delete room', $e);
        }
    }
    
    /**
     * Update room status
     */
    public function updateRoomStatus(int $id): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $data = $this->getRequestData();
            if (!$data) return;
            
            if (!isset($data['status'])) {
                ResponseService::Error('Status is required', 400);
                return;
            }
            
            if (!$this->validateStatus($data['status'])) {
                ResponseService::Error('Invalid status', 400);
                return;
            }
            
            $existingRoom = $this->roomModel->getById($id);
            if (!$existingRoom) {
                $this->handleNotFound('Room');
                return;
            }
            
            $success = $this->roomModel->updateStatus($id, $data['status']);
            if (!$success) {
                ResponseService::Error('Failed to update room status', 500);
                return;
            }
            
            $updatedRoom = $this->roomModel->getById($id);
            ResponseService::Send([
                'message' => 'Room status updated successfully',
                'room' => RoomDTO::fromArray($updatedRoom)->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update room status', $e);
        }
    }
    
    /**
     * Get rooms by status
     */
    public function getRoomsByStatus(string $status): void
    {
        try {
            if (!$this->validateStatus($status)) {
                ResponseService::Error('Invalid status', 400);
                return;
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
            
            $rooms = $this->roomModel->getByStatus($status, $page, $limit);
            $totalCount = $this->roomModel->getTotalCountByStatus($status);
            
            $roomDTOs = $this->mapToDTOs($rooms, RoomDTO::class);
            $this->sendPaginatedResponse($roomDTOs, $page, $limit, $totalCount, 'data');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve rooms by status', $e);
        }
    }    /**
     * Get room status statistics
     */
    public function getRoomStatusStats(): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // First check if we can get basic room count for debugging
            $totalRooms = $this->roomModel->getTotalCount();
            error_log("Total rooms in database: " . $totalRooms);
            
            $stats = $this->roomModel->getStatusCounts();
            
            // Log the stats being returned for debugging
            error_log("Room status stats: " . json_encode($stats));
            
            ResponseService::Send($stats);
            
        } catch (\PDOException $e) {
            error_log("Database error in getRoomStatusStats: " . $e->getMessage());
            ResponseService::Error('Database connection error: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            error_log("Error in getRoomStatusStats: " . $e->getMessage());
            $this->handleServerError('Failed to retrieve room statistics', $e);
        }
    }
      /**
     * Debug version of room status statistics (no auth required)
     */
    public function getRoomStatusStatsDebug(): void
    {
        try {
            // Test database connection and query
            $totalRooms = $this->roomModel->getTotalCount();
            $stats = $this->roomModel->getStatusCounts();
            
            ResponseService::Send([
                'message' => 'Debug endpoint working',
                'timestamp' => date('Y-m-d H:i:s'),
                'totalRooms' => $totalRooms,
                'statusCounts' => $stats
            ]);
            
        } catch (\Exception $e) {
            error_log("Error in getRoomStatusStatsDebug: " . $e->getMessage());
            ResponseService::Error('Debug endpoint error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Check room availability
     */
    public function checkAvailability(): void
    {
        try {
            if (!$this->validateQueryParams(['check_in', 'check_out'])) {
                return;
            }
            
            $checkIn = $_GET['check_in'];
            $checkOut = $_GET['check_out'];
            $roomTypeId = !empty($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : null;
            
            if (!$this->validateDates($checkIn, $checkOut)) {
                ResponseService::Error('Invalid dates', 400);
                return;
            }
            
            $availableRooms = $this->roomModel->checkAvailabilityForDates($checkIn, $checkOut, $roomTypeId);
            ResponseService::Send(['available_rooms' => $availableRooms]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to check availability', $e);
        }
    }

    /**
     * Validate if room type exists
     */
    private function validateRoomTypeExists(int $roomTypeId): bool
    {
        $roomType = $this->roomTypeModel->getById($roomTypeId);
        return $roomType !== null;
    }

    /**
     * Validate room status
     */
    private function validateStatus(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES);
    }

    /**
     * Validate date parameters
     */
    private function validateDates(?string $checkIn, ?string $checkOut): bool
    {
        if (!$checkIn || !$checkOut) {
            return false;
        }
        
        $checkInDate = \DateTime::createFromFormat('Y-m-d', $checkIn);
        $checkOutDate = \DateTime::createFromFormat('Y-m-d', $checkOut);
        
        if (!$checkInDate || !$checkOutDate) {
            return false;
        }
        
        return $checkOutDate > $checkInDate && $checkInDate >= new \DateTime('today');
    }
}
