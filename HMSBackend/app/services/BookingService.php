<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\DTO\Booking\BookingDTO;
use App\DTO\Booking\BookingCreateDTO;
use App\DTO\Booking\BookingStatusUpdateDTO;
use App\DTO\Booking\AvailabilitySearchDTO;

class BookingService extends BaseService
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
        $this->bookingModel = new Booking();
        $this->roomModel = new Room();
        $this->userModel = new User();
        $this->emailService = new EmailService();
    }

    /**
     * Get bookings with pagination and filters
     */
    public function getBookings(int $page, int $limit, array $filters = []): array
    {
        try {
            $bookings = $this->bookingModel->getAll($page, $limit, $filters);
            $totalCount = $this->bookingModel->getTotalCount($filters);

            $bookingDTOs = $this->mapToDTOs($bookings, BookingDTO::class);

            return [
                'success' => true,
                'data' => $bookingDTOs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'pages' => ceil($totalCount / $limit)
                ]
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to retrieve bookings', $e);
            return ['success' => false, 'message' => 'Failed to retrieve bookings'];
        }
    }

    /**
     * Get booking by ID
     */
    public function getBookingById(int $id): array
    {
        try {
            $booking = $this->bookingModel->getById($id);
            
            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }

            return [
                'success' => true,
                'data' => BookingDTO::fromArray($booking)->toArray()
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to retrieve booking', $e);
            return ['success' => false, 'message' => 'Failed to retrieve booking'];
        }
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $data, int $customerId): array
    {
        // Set customer ID
        $data['customer_id'] = $customerId;
        
        $bookingDTO = BookingCreateDTO::fromArray($data);
        $errors = $this->validateDTO($bookingDTO);
        
        if ($this->hasValidationErrors($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Validate customer exists
            $customer = $this->userModel->findById($bookingDTO->customer_id);
            if (!$customer) {
                return ['success' => false, 'message' => 'Customer not found'];
            }

            // Validate room exists and get room details
            $room = $this->roomModel->getById($bookingDTO->room_id);
            if (!$room) {
                return ['success' => false, 'message' => 'Room not found'];
            }

            // Check if room can accommodate guests
            if ($bookingDTO->guests > $room['capacity']) {
                return ['success' => false, 'message' => "Room can only accommodate {$room['capacity']} guests"];
            }

            // Check room availability
            if (!$this->bookingModel->checkRoomAvailability($bookingDTO->room_id, $bookingDTO->check_in, $bookingDTO->check_out)) {
                return ['success' => false, 'message' => 'Room is not available for the selected dates'];
            }

            // Create booking
            $id = $this->bookingModel->create($bookingDTO->toArray());
            $booking = $this->bookingModel->getById($id);

            // Send booking confirmation email
            $emailSent = $this->emailService->sendBookingConfirmation(
                $customer['email'],
                $customer['first_name'],
                $booking
            );

            if (!$emailSent) {
                $this->logError("Failed to send booking confirmation email to {$customer['email']}");
            }

            return [
                'success' => true,
                'data' => BookingDTO::fromArray($booking)->toArray(),
                'email_sent' => $emailSent
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to create booking', $e);
            return ['success' => false, 'message' => 'Failed to create booking'];
        }
    }

    /**
     * Update booking status
     */
    public function updateBookingStatus(int $id, array $data): array
    {
        $statusDTO = BookingStatusUpdateDTO::fromArray($data);
        $errors = $this->validateDTO($statusDTO);
        
        if ($this->hasValidationErrors($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Check if booking exists
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }

            // Check if status transition is valid
            if (!$this->isValidStatusTransition($booking['status'], $statusDTO->status)) {
                return ['success' => false, 'message' => "Cannot change booking status from '{$booking['status']}' to '{$statusDTO->status}'"];
            }

            // Update booking status
            $success = $this->bookingModel->updateStatus($id, $statusDTO->status);
            
            if (!$success) {
                return ['success' => false, 'message' => 'Failed to update booking status'];
            }

            // Get updated booking
            $updatedBooking = $this->bookingModel->getById($id);

            return [
                'success' => true,
                'data' => BookingDTO::fromArray($updatedBooking)->toArray()
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to update booking status', $e);
            return ['success' => false, 'message' => 'Failed to update booking status'];
        }
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(int $id): array
    {
        try {
            // Check if booking exists
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }

            // Check if booking can be cancelled
            if (!in_array($booking['status'], ['confirmed'])) {
                return ['success' => false, 'message' => 'Booking cannot be cancelled in its current status'];
            }

            // Cancel booking
            $success = $this->bookingModel->cancel($id);
            
            if (!$success) {
                return ['success' => false, 'message' => 'Failed to cancel booking'];
            }

            // Get updated booking
            $updatedBooking = $this->bookingModel->getById($id);

            return [
                'success' => true,
                'data' => BookingDTO::fromArray($updatedBooking)->toArray()
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to cancel booking', $e);
            return ['success' => false, 'message' => 'Failed to cancel booking'];
        }
    }    /**
     * Check room availability
     */
    public function checkAvailability(array $data): array
    {
        $searchDTO = AvailabilitySearchDTO::fromArray($data);
        $errors = $this->validateDTO($searchDTO);
        
        if ($this->hasValidationErrors($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Get all rooms and filter by availability
            $allRooms = $this->roomModel->getAll(1, 100); // Get reasonable amount
            $availableRooms = [];

            foreach ($allRooms as $room) {
                if ($this->bookingModel->checkRoomAvailability($room['id'], $data['check_in'], $data['check_out'])) {
                    // Only include rooms that can accommodate the guests
                    if ($room['capacity'] >= ($data['guests'] ?? 1)) {
                        $availableRooms[] = $room;
                    }
                }
            }

            return [
                'success' => true,
                'data' => $availableRooms
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to check availability', $e);
            return ['success' => false, 'message' => 'Failed to check availability'];
        }
    }

    /**
     * Check if user can access booking
     */
    public function canUserAccessBooking(array $user, array $booking): bool
    {
        // Admins and employees can access all bookings
        if (in_array($user['role'], ['admin', 'employee'])) {
            return true;
        }

        // Customers can only access their own bookings
        return $user['id'] === $booking['customer_id'];
    }

    /**
     * Validate if status transition is allowed
     */
    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_STATUS_TRANSITIONS[$currentStatus] ?? []);
    }
}
