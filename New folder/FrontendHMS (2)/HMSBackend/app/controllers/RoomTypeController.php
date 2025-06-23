<?php

namespace App\Controllers;

use App\Models\RoomType;
use App\DTO\Room\RoomTypeDTO;
use App\DTO\Room\RoomTypeCreateDTO;
use App\Services\ResponseService;

class RoomTypeController extends Controller
{
    private RoomType $roomTypeModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->roomTypeModel = new RoomType();
    }
      /**
     * Get all room types with pagination
     */
    public function getAllRoomTypes()
    {
        try {
            [$page, $limit] = $this->getPaginationParams();
            
            // Get room types
            $roomTypes = $this->roomTypeModel->getAll($page, $limit);
            $totalCount = $this->roomTypeModel->getTotalCount();
            
            // Create DTOs
            $roomTypeDTOs = array_map(function ($roomType) {
                return RoomTypeDTO::fromArray($roomType)->toArray();
            }, $roomTypes);
            
            // Send paginated response
            $this->sendPaginatedResponse($roomTypeDTOs, $page, $limit, $totalCount, 'roomTypes');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve room types', $e);
        }
    }
      /**
     * Get a specific room type by ID
     */
    public function getRoomType($id)
    {
        try {
            // Get room type
            $roomType = $this->roomTypeModel->getById((int)$id);
            
            if (!$roomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            // Return response
            ResponseService::Send(RoomTypeDTO::fromArray($roomType)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve room type', $e);
        }
    }
      /**
     * Create a new room type
     */
    public function createRoomType()
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Parse input
            $data = $this->decodePostData();
            if (!$data) return;
            
            // Create and validate DTO
            $roomTypeDTO = RoomTypeCreateDTO::fromArray($data);
            
            if ($this->handleValidationErrors($roomTypeDTO->validate())) {
                return;
            }
            
            // Create room type
            $id = $this->roomTypeModel->create($roomTypeDTO->toArray());
            
            // Get created room type
            $roomType = $this->roomTypeModel->getById($id);
            
            // Return response
            ResponseService::Send(RoomTypeDTO::fromArray($roomType)->toArray(), 201);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to create room type', $e);
        }
    }
      /**
     * Update an existing room type
     */
    public function updateRoomType($id)
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Parse input
            $data = $this->decodePostData();
            if (!$data) return;
            
            // Check if room type exists
            $existingRoomType = $this->roomTypeModel->getById((int)$id);
            if (!$existingRoomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            // Create and validate DTO
            $roomTypeDTO = RoomTypeCreateDTO::fromArray($data);
            
            if ($this->handleValidationErrors($roomTypeDTO->validate())) {
                return;
            }
            
            // Update room type
            $updated = $this->roomTypeModel->update((int)$id, $roomTypeDTO->toArray());
            
            if (!$updated) {
                $this->handleServerError("Failed to update room type");
                return;
            }
            
            // Get updated room type
            $roomType = $this->roomTypeModel->getById((int)$id);
            
            // Return response
            ResponseService::Send(RoomTypeDTO::fromArray($roomType)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update room type', $e);
        }
    }
      /**
     * Delete a room type
     */
    public function deleteRoomType($id)
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            // Check if room type exists
            $existingRoomType = $this->roomTypeModel->getById((int)$id);
            if (!$existingRoomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            // Delete room type
            $deleted = $this->roomTypeModel->delete((int)$id);
            
            if (!$deleted) {
                ResponseService::Error("Cannot delete room type because it is in use", 400);
                return;
            }
            
            // Return response
            ResponseService::Send(null, 204);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to delete room type', $e);
        }
    }
}
