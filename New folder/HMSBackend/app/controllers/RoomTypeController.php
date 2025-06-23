<?php

namespace App\Controllers;

use App\Models\RoomType;
use App\DTO\Room\RoomTypeDTO;
use App\DTO\Room\RoomTypeCreateDTO;
use App\Services\LegacyResponseAdapter as ResponseService;

class RoomTypeController extends Controller
{
    private RoomType $roomTypeModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->roomTypeModel = new RoomType();
    }    /**
     * Get all room types with pagination
     */
    public function getAllRoomTypes(): void
    {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
            
            $roomTypes = $this->roomTypeModel->getAll($page, $limit);
            $totalCount = $this->roomTypeModel->getTotalCount();
            
            $roomTypeDTOs = $this->mapToDTOs($roomTypes, RoomTypeDTO::class);
            $this->sendPaginatedResponse($roomTypeDTOs, $page, $limit, $totalCount, 'data');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve room types', $e);
        }
    }

    /**
     * Get a specific room type by ID
     */
    public function getRoomType(int $id): void
    {
        try {
            $roomType = $this->roomTypeModel->getById($id);
            if (!$roomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            ResponseService::Send(RoomTypeDTO::fromArray($roomType)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve room type', $e);
        }
    }

    /**
     * Create a new room type
     */
    public function createRoomType(): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $data = $this->getRequestData();
            if (!$data) return;
            
            $roomTypeDTO = RoomTypeCreateDTO::fromArray($data);
            if ($this->handleValidationErrors($roomTypeDTO->validate())) return;
            
            $id = $this->roomTypeModel->create($roomTypeDTO->toArray());
            $roomType = $this->roomTypeModel->getById($id);
            
            ResponseService::Send(RoomTypeDTO::fromArray($roomType)->toArray(), 201);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to create room type', $e);
        }
    }

    /**
     * Update an existing room type
     */
    public function updateRoomType(int $id): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $data = $this->getRequestData();
            if (!$data) return;
            
            $existingRoomType = $this->roomTypeModel->getById($id);
            if (!$existingRoomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            $roomTypeDTO = RoomTypeCreateDTO::fromArray($data);
            if ($this->handleValidationErrors($roomTypeDTO->validate())) return;
            
            $success = $this->roomTypeModel->update($id, $roomTypeDTO->toArray());
            if (!$success) {
                ResponseService::Error('Failed to update room type', 500);
                return;
            }
            
            $updatedRoomType = $this->roomTypeModel->getById($id);
            ResponseService::Send(RoomTypeDTO::fromArray($updatedRoomType)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update room type', $e);
        }
    }

    /**
     * Delete a room type
     */
    public function deleteRoomType(int $id): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $existingRoomType = $this->roomTypeModel->getById($id);
            if (!$existingRoomType) {
                $this->handleNotFound("Room type");
                return;
            }
            
            $success = $this->roomTypeModel->delete($id);
            if (!$success) {
                ResponseService::Error('Cannot delete room type - rooms are still using this type', 400);
                return;
            }
            
            ResponseService::Send(['message' => 'Room type deleted successfully']);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to delete room type', $e);
        }
    }
}
