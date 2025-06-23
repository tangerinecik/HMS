<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\DTO\Booking\BookingDTO;
use App\DTO\Booking\BookingCreateDTO;
use App\DTO\Booking\BookingStatusUpdateDTO;
use App\DTO\Booking\AvailabilitySearchDTO;
use App\Services\ResponseService;

class BookingController extends Controller
{
    private Booking $bookingModel;
    private Room $roomModel;
    private User $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new Booking();
        $this->roomModel = new Room();
        $this->userModel = new User();
    }
    
    /**
     * Get all bookings with pagination and optional filters
     */
    public function getAllBookings()
    {
        // Validate user is admin or employee
        $user = $this->authMiddleware->requireAnyRole(['admin', 'employee']);
        if (!$user) return;
        
        try {
            [$page, $limit] = $this->getPaginationParams();
            
            // Get optional filters
            $filters = [];
            if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
                $filters['customer_id'] = (int) $_GET['customer_id'];
            }
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['room_id']) && !empty($_GET['room_id'])) {
                $filters['room_id'] = (int) $_GET['room_id'];
            }
            if (isset($_GET['check_in_from']) && !empty($_GET['check_in_from'])) {
                $filters['check_in_from'] = $_GET['check_in_from'];
            }
            if (isset($_GET['check_in_to']) && !empty($_GET['check_in_to'])) {
                $filters['check_in_to'] = $_GET['check_in_to'];
            }
            
            // Get bookings
            $bookings = $this->bookingModel->getAll($page, $limit, $filters);
            $totalCount = $this->bookingModel->getTotalCount($filters);
            
            // Create DTOs
            $bookingDTOs = array_map(function ($booking) {
                return BookingDTO::fromArray($booking)->toArray();
            }, $bookings);
            
            // Send paginated response
            $this->sendPaginatedResponse($bookingDTOs, $page, $limit, $totalCount, 'bookings');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve bookings', $e);
        }
    }
    
    /**
     * Get user's own bookings
     */
    public function getMyBookings()
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            [$page, $limit] = $this->getPaginationParams();
            
            // Filter by current user's ID
            $filters = ['customer_id' => $user->data->id];
            
            // Get optional status filter
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            // Get bookings
            $bookings = $this->bookingModel->getAll($page, $limit, $filters);
            $totalCount = $this->bookingModel->getTotalCount($filters);
            
            // Create DTOs
            $bookingDTOs = array_map(function ($booking) {
                return BookingDTO::fromArray($booking)->toArray();
            }, $bookings);
            
            // Send paginated response
            $this->sendPaginatedResponse($bookingDTOs, $page, $limit, $totalCount, 'bookings');
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve your bookings', $e);
        }
    }
    
    /**
     * Get a specific booking by ID
     */
    public function getBooking($id)
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $booking = $this->bookingModel->getById((int) $id);
            
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            // Check if user can access this booking
            if ($user->data->role === 'customer' && $booking['customer_id'] !== $user->data->id) {
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
    public function getBookingByRefCode($refCode)
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $booking = $this->bookingModel->getByRefCode($refCode);
            
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            // Check if user can access this booking
            if ($user->data->role === 'customer' && $booking['customer_id'] !== $user->data->id) {
                ResponseService::Error('Access denied - you can only view your own bookings', 403);
                return;
            }
            
            ResponseService::Send(BookingDTO::fromArray($booking)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve booking', $e);
        }
    }
    
    /**
     * Create a new booking
     */
    public function createBooking()
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            // Set customer_id to authenticated user (customers can only book for themselves)
            if ($user->data->role === 'customer') {
                $data['customer_id'] = $user->data->id;
            } elseif (!isset($data['customer_id'])) {
                // Employees/admins must specify customer_id
                ResponseService::Error('Customer ID is required', 400);
                return;
            }
            
            // Create and validate DTO
            $bookingDTO = BookingCreateDTO::fromArray($data);
            
            if ($this->handleValidationErrors($bookingDTO->validate())) {
                return;
            }
            
            // Validate customer exists
            $customer = $this->userModel->findById($bookingDTO->customer_id);
            if (!$customer) {
                ResponseService::Error('Customer not found', 404);
                return;
            }
            
            // Validate room exists and get room details
            $room = $this->roomModel->getById($bookingDTO->room_id);
            if (!$room) {
                ResponseService::Error('Room not found', 404);
                return;
            }
            
            // Check if room can accommodate guests
            if ($bookingDTO->guests > $room['capacity']) {
                ResponseService::Error("Room can only accommodate {$room['capacity']} guests", 400);
                return;
            }
            
            // Check room availability
            if (!$this->bookingModel->checkRoomAvailability($bookingDTO->room_id, $bookingDTO->check_in, $bookingDTO->check_out)) {
                ResponseService::Error('Room is not available for the selected dates', 409);
                return;
            }
            
            // Create booking
            $id = $this->bookingModel->create($bookingDTO->toArray());
            
            // Get created booking with full details
            $booking = $this->bookingModel->getById($id);
            
            ResponseService::Send(BookingDTO::fromArray($booking)->toArray(), 201);
            
        } catch (\InvalidArgumentException $e) {
            ResponseService::Error($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->handleServerError('Failed to create booking', $e);
        }
    }
    
    /**
     * Update booking status
     */
    public function updateBookingStatus($id)
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            // Check if booking exists
            $booking = $this->bookingModel->getById((int) $id);
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            // Check permissions
            if ($user->data->role === 'customer') {
                // Customers can only cancel their own bookings
                if ($booking['customer_id'] !== $user->data->id) {
                    ResponseService::Error('Access denied - you can only modify your own bookings', 403);
                    return;
                }
                // Customers can only cancel (not change to other statuses)
                if (!isset($data['status']) || $data['status'] !== 'cancelled') {
                    ResponseService::Error('Customers can only cancel bookings', 403);
                    return;
                }
            }
            
            // Create and validate DTO
            $statusDTO = BookingStatusUpdateDTO::fromArray($data);
            
            if ($this->handleValidationErrors($statusDTO->validate())) {
                return;
            }
            
            // Check if status change is valid
            if (!$this->isValidStatusTransition($booking['status'], $statusDTO->status)) {
                ResponseService::Error("Cannot change booking status from '{$booking['status']}' to '{$statusDTO->status}'", 400);
                return;
            }
            
            // Update booking status
            $success = $this->bookingModel->updateStatus((int) $id, $statusDTO->status);
            
            if (!$success) {
                ResponseService::Error('Failed to update booking status', 500);
                return;
            }
            
            // Get updated booking
            $updatedBooking = $this->bookingModel->getById((int) $id);
            
            ResponseService::Send(BookingDTO::fromArray($updatedBooking)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update booking status', $e);
        }
    }
    
    /**
     * Cancel a booking
     */
    public function cancelBooking($id)
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;
        
        try {
            // Check if booking exists
            $booking = $this->bookingModel->getById((int) $id);
            if (!$booking) {
                $this->handleNotFound('Booking');
                return;
            }
            
            // Check permissions - users can only cancel their own bookings
            if ($user->data->role === 'customer' && $booking['customer_id'] !== $user->data->id) {
                ResponseService::Error('Access denied - you can only cancel your own bookings', 403);
                return;
            }
            
            // Check if booking can be cancelled
            if (!in_array($booking['status'], ['confirmed'])) {
                ResponseService::Error('Booking cannot be cancelled in its current status', 400);
                return;
            }
            
            // Cancel booking
            $success = $this->bookingModel->cancel((int) $id);
            
            if (!$success) {
                ResponseService::Error('Failed to cancel booking', 500);
                return;
            }
            
            // Get updated booking
            $updatedBooking = $this->bookingModel->getById((int) $id);
            
            ResponseService::Send(BookingDTO::fromArray($updatedBooking)->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to cancel booking', $e);
        }
    }
    
    /**
     * Check room availability
     */
    public function checkAvailability()
    {
        try {
            $checkIn = $_GET['check_in'] ?? '';
            $checkOut = $_GET['check_out'] ?? '';
            $guests = (int) ($_GET['guests'] ?? 1);
            
            // Create and validate search DTO
            $searchDTO = new AvailabilitySearchDTO([
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => $guests
            ]);
            
            if ($this->handleValidationErrors($searchDTO->validate())) {
                return;
            }
            
            // Get available rooms
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
    public function getBookingStats()
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
     * Validate if status transition is allowed
     */
    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'confirmed' => ['checked_in', 'cancelled'],
            'checked_in' => ['checked_out'],
            'checked_out' => [], // Final state
            'cancelled' => []    // Final state
        ];
        
        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }
}
