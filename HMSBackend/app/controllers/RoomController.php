<?php

namespace App\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\DTO\Room\RoomDTO;
use App\DTO\Room\RoomCreateDTO;
use App\Services\ResponseService;

class RoomController extends Controller
{
    private Room $roomModel;
    private RoomType $roomTypeModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->roomModel = new Room();
        $this->roomTypeModel = new RoomType();
    }
      /**
     * Get all rooms with pagination
     */
    public function getAllRooms()
    {
        try {
            [$page, $limit] = $this->getPaginationParams();
            
            // Get room type filter if provided
            $roomTypeId = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : null;
            
            // Get rooms
            if ($roomTypeId) {
                $rooms = $this->roomModel->getRoomsByType($roomTypeId, $page, $limit);
                $totalCount = $this->roomModel->getTotalCountByType($roomTypeId);
            } else {
                $rooms = $this->roomModel->getAll($page, $limit);
                $totalCount = $this->roomModel->getTotalCount();
            }
            
            // Create DTOs
            $roomDTOs = array_map(function ($room) {
                return RoomDTO::fromArray($room)->toArray();
            }, $rooms);
            
            // Send paginated response
            $this->sendPaginatedResponse($roomDTOs, $page, $limit, $totalCount, 'rooms');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve rooms', $e);
        }
    }
      /**
     * Get a specific room by ID
     */
    public function getRoom($id)
    {
        try {
            // Get room
            $room = $this->roomModel->getById((int)$id);
            
            if (!$room) {
                $this->handleNotFound("Room");
                return;
            }
            
            // Return response
            ResponseService::Send(RoomDTO::fromArray($room)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve room', $e);
        }
    }
      /**
     * Create a new room
     */
    public function createRoom()
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Parse input
            $data = $this->decodePostData();
            if (!$data) return;
            
            // Create and validate DTO
            $roomDTO = RoomCreateDTO::fromArray($data);
            
            if ($this->handleValidationErrors($roomDTO->validate())) {
                return;
            }
            
            // Check if room type exists
            $roomType = $this->roomTypeModel->getById($roomDTO->room_type_id);
            if (!$roomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            // Create room
            $id = $this->roomModel->create($roomDTO->toArray());
            
            // Get created room
            $room = $this->roomModel->getById($id);
            
            // Return response
            ResponseService::Send(RoomDTO::fromArray($room)->toArray(), 201);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to create room', $e);
        }
    }
      /**
     * Update an existing room
     */
    public function updateRoom($id)
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Parse input
            $data = $this->decodePostData();
            if (!$data) return;
            
            // Check if room exists
            $existingRoom = $this->roomModel->getById((int)$id);
            if (!$existingRoom) {
                $this->handleNotFound("Room");
                return;
            }
            
            // Create and validate DTO
            $roomDTO = RoomCreateDTO::fromArray($data);
            
            if ($this->handleValidationErrors($roomDTO->validate())) {
                return;
            }
            
            // Check if room type exists
            $roomType = $this->roomTypeModel->getById($roomDTO->room_type_id);
            if (!$roomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            // Update room
            $updated = $this->roomModel->update((int)$id, $roomDTO->toArray());
            
            if (!$updated) {
                $this->handleServerError("Failed to update room");
                return;
            }
            
            // Get updated room
            $room = $this->roomModel->getById((int)$id);
            
            // Return response
            ResponseService::Send(RoomDTO::fromArray($room)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update room', $e);
        }
    }
    
    /**
     * Delete a room
     */
    public function deleteRoom($id)
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Check if room exists
            $existingRoom = $this->roomModel->getById((int)$id);
            if (!$existingRoom) {
                $this->handleNotFound("Room");
                return;
            }
            
            // Delete room
            $deleted = $this->roomModel->delete((int)$id);
            
            if (!$deleted) {
                ResponseService::Error("Cannot delete room because it is in use", 400);
                return;
            }
            
            // Return response with no content
            ResponseService::Send(null, 204);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to delete room', $e);
        }
    }
    
    /**
     * Update room status
     */
    public function updateRoomStatus($id)
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Check if room exists
            $existingRoom = $this->roomModel->getById((int)$id);
            if (!$existingRoom) {
                $this->handleNotFound("Room");
                return;
            }
            
            // Get request body
            $data = $this->decodePostData();
            if (!$data) return;
            
            if (!isset($data['status'])) {
                ResponseService::Error("Missing status field", 400);
                return;
            }
            
            $status = $data['status'];
            
            // Validate status
            $validStatuses = ['available', 'occupied', 'cleaning', 'maintenance', 'out_of_order'];
            if (!in_array($status, $validStatuses)) {
                ResponseService::Error("Invalid status. Must be one of: " . implode(', ', $validStatuses), 400);
                return;
            }
            
            // Update room status
            $updated = $this->roomModel->updateStatus((int)$id, $status);
            
            if (!$updated) {
                $this->handleServerError("Failed to update room status");
                return;
            }
            
            // Get updated room
            $room = $this->roomModel->getById((int)$id);
            
            // Return response
            ResponseService::Send(RoomDTO::fromArray($room)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update room status', $e);
        }
    }
    
    /**
     * Get rooms by status
     */
    public function getRoomsByStatus($status)
    {
        try {
            // Validate status
            $validStatuses = ['available', 'occupied', 'cleaning', 'maintenance', 'out_of_order'];
            if (!in_array($status, $validStatuses)) {
                ResponseService::Error("Invalid status. Must be one of: " . implode(', ', $validStatuses), 400);
                return;
            }
            
            [$page, $limit] = $this->getPaginationParams();
            
            // Get rooms by status
            $rooms = $this->roomModel->getByStatus($status, $page, $limit);
            
            // Map to DTOs
            $roomDTOs = array_map(fn($room) => RoomDTO::fromArray($room)->toArray(), $rooms);
            
            // Return response
            ResponseService::Send($roomDTOs);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve rooms by status', $e);
        }
    }
    
    /**
     * Get room status statistics
     */
    public function getRoomStatusStats()
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Get status counts
            $statusCounts = $this->roomModel->getStatusCounts();
            
            // Ensure all statuses are included (with 0 count if none exist)
            $validStatuses = ['available', 'occupied', 'cleaning', 'maintenance', 'out_of_order'];
            $stats = [];
            
            foreach ($validStatuses as $status) {
                $stats[$status] = $statusCounts[$status] ?? 0;
            }
            
            // Return response
            ResponseService::Send($stats);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve room statistics', $e);
        }
    }
    
    /**
     * Get room availability for specific dates
     */
    public function checkAvailability()
    {
        try {
            // Get query parameters
            $checkIn = $_GET['check_in'] ?? null;
            $checkOut = $_GET['check_out'] ?? null;
            $roomTypeId = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : null;
            
            // Validate dates
            if (!$checkIn || !$checkOut) {
                ResponseService::Error("Check-in and check-out dates are required", 400);
                return;
            }
            
            // Validate date format and logic
            $checkInDate = \DateTime::createFromFormat('Y-m-d', $checkIn);
            $checkOutDate = \DateTime::createFromFormat('Y-m-d', $checkOut);
            
            if (!$checkInDate || !$checkOutDate) {
                ResponseService::Error("Invalid date format. Use YYYY-MM-DD", 400);
                return;
            }
            
            if ($checkInDate >= $checkOutDate) {
                ResponseService::Error("Check-out date must be after check-in date", 400);
                return;
            }
            
            // Use the availability checking method from the model
            $availability = $this->roomModel->checkAvailabilityForDates($checkIn, $checkOut, $roomTypeId);
            
            // Calculate booked rooms (total - available)
            $booked = $availability['total'] - $availability['available'];
            
            // Return availability data
            ResponseService::Send([
                'total' => $availability['total'],
                'booked' => $booked,
                'available' => $availability['available'],
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'room_type_id' => $roomTypeId
            ]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to check availability', $e);
        }
    }
}
