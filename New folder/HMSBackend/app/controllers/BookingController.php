<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\DTO\Booking\BookingDTO;
use App\DTO\Booking\BookingCreateDTO;
use App\DTO\Booking\BookingStatusUpdateDTO;
use App\DTO\Booking\AvailabilitySearchDTO;
use App\Services\LegacyResponseAdapter as ResponseService;
use App\Services\EmailService;

class BookingController extends Controller
{
    private Booking $bookingModel;
    private Room $roomModel;
    private User $userModel;
    private EmailService $emailService;
    
    private const VALID_STATUS_TRANSITIONS = [
        'confirmed' => ['checked_in', 'cancelled'],
        'checked_in' => ['checked_out'],
        'checked_out' => [], // Final state
        'cancelled' => []    // Final state
    ];
      public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new Booking();
        $this->roomModel = new Room();
        $this->userModel = new User();
        $this->emailService = $this->container->resolve('email_service');
    }
    
    /**
     * Get all bookings with pagination and filters (admin/employee only)
     */
    public function getAllBookings(): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            [$page, $limit] = $this->getPaginationParams();
            $filters = $this->extractBookingFilters();
            
            $bookings = $this->bookingModel->getAll($page, $limit, $filters);
            $totalCount = $this->bookingModel->getTotalCount($filters);
            
            $bookingDTOs = $this->mapToDTOs($bookings, BookingDTO::class);
            $this->sendPaginatedResponse($bookingDTOs, $page, $limit, $totalCount, 'bookings');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve bookings', $e);
        }
    }
    
    /**
     * Get user's own bookings
     */
    public function getMyBookings(): void
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            [$page, $limit] = $this->getPaginationParams();
            $filters = ['customer_id' => $user->data->id];
            
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            $bookings = $this->bookingModel->getAll($page, $limit, $filters);
            $totalCount = $this->bookingModel->getTotalCount($filters);
            
            $bookingDTOs = $this->mapToDTOs($bookings, BookingDTO::class);
            $this->sendPaginatedResponse($bookingDTOs, $page, $limit, $totalCount, 'bookings');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve your bookings', $e);
        }
    }
    
    /**
     * Get a specific booking by ID
     */
    public function getBooking(int $id): void
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            if (!$this->canAccessBooking($user, $booking)) {
                ResponseService::Error('Access denied - you can only view your own bookings', 403);
                return;
            }
            
            ResponseService::Send(BookingDTO::fromArray($booking)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve booking', $e);
        }
    }
    
    /**
     * Get booking by reference code
     */
    public function getBookingByRefCode(string $refCode): void
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $booking = $this->bookingModel->getByRefCode($refCode);
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            if (!$this->canAccessBooking($user, $booking)) {
                ResponseService::Error('Access denied - you can only view your own bookings', 403);
                return;
            }
            
            ResponseService::Send(BookingDTO::fromArray($booking)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve booking', $e);
        }
    }
    
    /**
     * Create a new booking with overlap prevention
     */
    public function createBooking(): void
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $data = $this->getRequestData();
            if (!$data) return;
            
            // Set customer_id based on user role
            $data = $this->setCustomerIdFromUser($user, $data);
            if (!$data) return;
            
            $bookingDTO = BookingCreateDTO::fromArray($data);
            if ($this->handleValidationErrors($bookingDTO->validate())) return;
            
            // Validate customer and room exist
            if (!$this->validateCustomerExists($bookingDTO->customer_id)) return;
            $room = $this->getRoomById($bookingDTO->room_id);
            if (!$room) return;
            
            // Validate room capacity
            if (!$this->validateRoomCapacity($room, $bookingDTO->guests)) return;
            
            // Critical: Check room availability to prevent overlapping bookings
            if (!$this->validateRoomAvailability($bookingDTO->room_id, $bookingDTO->check_in, $bookingDTO->check_out)) {
                ResponseService::Error('Room is not available for the selected dates', 409);
                return;
            }
              $id = $this->bookingModel->create($bookingDTO->toArray());
            $booking = $this->bookingModel->getById($id);
            
            // Send booking confirmation email
            $customer = $this->userModel->findById($bookingDTO->customer_id);
            if ($customer) {
                $emailSent = $this->emailService->sendBookingConfirmation(
                    $customer['email'],
                    $customer['first_name'],
                    $booking
                );
                
                if (!$emailSent) {
                    error_log("Failed to send booking confirmation email to {$customer['email']}");
                }
            }
            
            ResponseService::Send(BookingDTO::fromArray($booking)->toArray(), 201);
            
        } catch (\InvalidArgumentException $e) {
            ResponseService::Error($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->handleServerError('Failed to create booking', $e);
        }
    }
    
    /**
     * Update booking status with proper validation
     */
    public function updateBookingStatus(int $id): void
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $data = $this->getRequestData();
            if (!$data) return;
            
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            if (!$this->canModifyBooking($user, $booking, $data)) return;
            
            $statusDTO = BookingStatusUpdateDTO::fromArray($data);
            if ($this->handleValidationErrors($statusDTO->validate())) return;
              if (!$this->isValidStatusTransition($booking['status'], $statusDTO->status)) {
                ResponseService::Error("Cannot change booking status from '{$booking['status']}' to '{$statusDTO->status}'", 400);
                return;
            }
            
            $success = $this->bookingModel->updateStatus($id, $statusDTO->status);
            if (!$success) {
                $this->handleServerError('Failed to update booking status');
                return;
            }
            
            $updatedBooking = $this->bookingModel->getById($id);
            ResponseService::Send(BookingDTO::fromArray($updatedBooking)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update booking status', $e);
        }
    }
    
    /**
     * Cancel a booking (simplified version of status update)
     */
    public function cancelBooking(int $id): void
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            if (!$this->canAccessBooking($user, $booking)) {
                ResponseService::Error('Access denied - you can only cancel your own bookings', 403);
                return;
            }
            
            if (!in_array($booking['status'], ['confirmed'])) {
                ResponseService::Error('Booking cannot be cancelled in its current status', 400);
                return;
            }
            
            $success = $this->bookingModel->cancel($id);
            if (!$success) {
                $this->handleServerError('Failed to cancel booking');
                return;
            }
            
            $updatedBooking = $this->bookingModel->getById($id);
            ResponseService::Send(BookingDTO::fromArray($updatedBooking)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to cancel booking', $e);
        }
    }
    
    /**
     * Check room availability for given dates
     */
    public function checkAvailability(): void
    {
        try {
            $checkIn = $_GET['check_in'] ?? '';
            $checkOut = $_GET['check_out'] ?? '';
            $guests = (int) ($_GET['guests'] ?? 1);
            
            $searchDTO = new AvailabilitySearchDTO([
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => $guests
            ]);
            
            if ($this->handleValidationErrors($searchDTO->validate())) return;
            
            $availableRooms = $this->bookingModel->getAvailableRooms($checkIn, $checkOut, $guests);
            
            ResponseService::Send([
                'available_rooms' => $availableRooms,
                'search_criteria' => [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'guests' => $guests
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to check availability', $e);
        }
    }
    
    /**
     * Get booking statistics (admin/employee only)
     */
    public function getBookingStats(): void
    {
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            $stats = $this->bookingModel->getBookingStats();
            
            ResponseService::Send([
                'stats' => $stats,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve booking statistics', $e);
        }
    }

    /**
     * Extract booking filters from query parameters
     */
    private function extractBookingFilters(): array
    {
        $allowedFilters = ['customer_id', 'status', 'room_id', 'check_in_from', 'check_in_to'];
        return $this->extractFilters($allowedFilters);
    }

    /**
     * Check if user can access a specific booking
     */
    private function canAccessBooking(object $user, array $booking): bool
    {
        return $user->data->role !== 'customer' || $booking['customer_id'] === $user->data->id;
    }

    /**
     * Check if user can modify a booking based on role and data
     */
    private function canModifyBooking(object $user, array $booking, array $data): bool
    {
        if ($user->data->role === 'customer') {
            if ($booking['customer_id'] !== $user->data->id) {
                ResponseService::Error('Access denied - you can only modify your own bookings', 403);
                return false;
            }
            if (!isset($data['status']) || $data['status'] !== 'cancelled') {
                ResponseService::Error('Customers can only cancel bookings', 403);
                return false;
            }
        }
        return true;
    }

    /**
     * Set customer_id based on user role and input data
     */
    private function setCustomerIdFromUser(object $user, array $data): ?array
    {
        if ($user->data->role === 'customer') {
            $data['customer_id'] = $user->data->id;
        } elseif (!isset($data['customer_id'])) {
            ResponseService::Error('Customer ID is required', 400);
            return null;
        }
        return $data;
    }

    /**
     * Validate customer exists
     */
    private function validateCustomerExists(int $customerId): bool
    {
        $customer = $this->userModel->findById($customerId);
        if (!$customer) {
            ResponseService::Error('Customer not found', 404);
            return false;
        }
        return true;
    }

    /**
     * Get room by ID with validation
     */
    private function getRoomById(int $roomId): ?array
    {
        $room = $this->roomModel->getById($roomId);
        if (!$room) {
            ResponseService::Error('Room not found', 404);
            return null;
        }
        return $room;
    }

    /**
     * Validate room can accommodate guests
     */
    private function validateRoomCapacity(array $room, int $guests): bool
    {
        if ($guests > $room['capacity']) {
            ResponseService::Error("Room can only accommodate {$room['capacity']} guests", 400);
            return false;
        }
        return true;
    }

    /**
     * Critical validation: Prevent overlapping bookings
     */
    private function validateRoomAvailability(int $roomId, string $checkIn, string $checkOut): bool
    {
        return $this->bookingModel->checkRoomAvailability($roomId, $checkIn, $checkOut);
    }    /**
     * Validate booking status transitions
     */
    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_STATUS_TRANSITIONS[$currentStatus] ?? []);
    }
}
