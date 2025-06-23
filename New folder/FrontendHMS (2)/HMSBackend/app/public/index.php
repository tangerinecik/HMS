<?php

/**
 * Setup
 */

// Start output buffering to prevent headers already sent issues
ob_start();

// require autoload file to autoload vendor libraries
require_once __DIR__ . '/../vendor/autoload.php';

// require local classes
use App\Services\EnvService;
use App\Services\ErrorReportingService;
use App\Services\ResponseService;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\BookingController;
use App\Controllers\RoomController;
use App\Controllers\RoomTypeController;

// require vendor libraries
use Steampixel\Route;

// initialize global environment variables
EnvService::Init();

// initialize error reporting (on in local env)
ErrorReportingService::Init();

// set CORS headers
ResponseService::SetCorsHeaders();

/**
 * Main application routes
 */
// top level fail-safe try/catch
try {
    /**
     * Health check route
     */
    Route::add('/', function () {
        ResponseService::Send([
            'message' => 'HMS API is running',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'auth' => '/api/auth/*',
                'users' => '/api/users/*',
                'rooms' => '/api/rooms/*',
                'room-types' => '/api/room-types/*',
                'bookings' => '/api/bookings/*',
                'payments' => '/api/payments/*'
            ]
        ]);
    }, ["get"]);

    /**
     * Auth routes
     */
    Route::add('/auth/register', function () {
        $authController = new AuthController();
        $authController->register();
    }, ["post"]);

    Route::add('/auth/login', function () {
        $authController = new AuthController();
        $authController->login();
    }, ["post"]);

    Route::add('/auth/me', function () {
        $authController = new AuthController();
        $authController->me();
    }, ["get"]);

    Route::add('/auth/refresh', function () {
        $authController = new AuthController();
        $authController->refresh();
    }, ["post"]);

    Route::add('/auth/logout', function () {
        $authController = new AuthController();
        $authController->logout();
    }, ["post"]);

    Route::add('/auth/verify-email', function () {
        $authController = new AuthController();
        $authController->verifyEmail();
    }, ["post"]);

    Route::add('/auth/resend-verification', function () {
        $authController = new AuthController();
        $authController->resendVerification();
    }, ["post"]);

    /**
     * User management routes
     */
    Route::add('/users', function () {
        $userController = new UserController();
        $userController->getAllUsers();
    }, ["get"]);
    
    Route::add('/users/([0-9]+)', function ($id) {
        $userController = new UserController();
        $userController->getUser($id);
    }, ["get"]);
    
    Route::add('/users/([0-9]+)', function ($id) {
        $userController = new UserController();
        $userController->updateUser($id);
    }, ["put"]);
    
    Route::add('/users/([0-9]+)', function ($id) {
        $userController = new UserController();
        $userController->deleteUser($id);
    }, ["delete"]);

    Route::add('/users/([0-9]+)/password', function ($id) {
        $userController = new UserController();
        $userController->changePassword($id);
    }, ["put"]);

    /**
     * Room Type Management Routes
     */
    Route::add('/room-types', function () {
        $roomTypeController = new RoomTypeController();
        $roomTypeController->getAllRoomTypes();
    }, ["get"]);

    Route::add('/room-types/([0-9]+)', function ($id) {
        $roomTypeController = new RoomTypeController();
        $roomTypeController->getRoomType($id);
    }, ["get"]);

    Route::add('/room-types', function () {
        $roomTypeController = new RoomTypeController();
        $roomTypeController->createRoomType();
    }, ["post"]);

    Route::add('/room-types/([0-9]+)', function ($id) {
        $roomTypeController = new RoomTypeController();
        $roomTypeController->updateRoomType($id);
    }, ["put"]);

    Route::add('/room-types/([0-9]+)', function ($id) {
        $roomTypeController = new RoomTypeController();
        $roomTypeController->deleteRoomType($id);
    }, ["delete"]);

    /**
     * Room Management Routes
     */
    Route::add('/rooms', function () {
        $roomController = new RoomController();
        $roomController->getAllRooms();
    }, ["get"]);

    Route::add('/rooms/([0-9]+)', function ($id) {
        $roomController = new RoomController();
        $roomController->getRoom($id);
    }, ["get"]);

    Route::add('/rooms', function () {
        $roomController = new RoomController();
        $roomController->createRoom();
    }, ["post"]);

    Route::add('/rooms/([0-9]+)', function ($id) {
        $roomController = new RoomController();
        $roomController->updateRoom($id);
    }, ["put"]);

    Route::add('/rooms/([0-9]+)', function ($id) {
        $roomController = new RoomController();
        $roomController->deleteRoom($id);
    }, ["delete"]);

    // Room status management routes
    Route::add('/rooms/([0-9]+)/status', function ($id) {
        $roomController = new RoomController();
        $roomController->updateRoomStatus($id);
    }, ["put"]);

    Route::add('/rooms/status/([a-z_]+)', function ($status) {
        $roomController = new RoomController();
        $roomController->getRoomsByStatus($status);
    }, ["get"]);

    Route::add('/rooms/status/stats', function () {
        $roomController = new RoomController();
        $roomController->getRoomStatusStats();
    }, ["get"]);

    // Room availability checking route
    Route::add('/rooms/availability', function () {
        $roomController = new RoomController();
        $roomController->checkAvailability();
    }, ["get"]);

    /**
     * Booking Management Routes
     */
    // Search availability
    Route::add('/bookings/availability', function () {
        $bookingController = new BookingController();
        $bookingController->checkAvailability();
    }, ["get"]);

    // Create booking (requires authentication)
    Route::add('/bookings', function () {
        $bookingController = new BookingController();
        $bookingController->createBooking();
    }, ["post"]);

    // Get user's own bookings
    Route::add('/bookings/my', function () {
        $bookingController = new BookingController();
        $bookingController->getMyBookings();
    }, ["get"]);

    // Get all bookings (admin/employee only)
    Route::add('/bookings/all', function () {
        $bookingController = new BookingController();
        $bookingController->getAllBookings();
    }, ["get"]);

    // Get booking statistics (admin/employee only)
    Route::add('/bookings/stats', function () {
        $bookingController = new BookingController();
        $bookingController->getBookingStats();
    }, ["get"]);

    // Get booking by reference code
    Route::add('/bookings/ref/([A-Z0-9]+)', function ($refCode) {
        $bookingController = new BookingController();
        $bookingController->getBookingByRefCode($refCode);
    }, ["get"]);

    // Cancel booking
    Route::add('/bookings/([0-9]+)/cancel', function ($id) {
        $bookingController = new BookingController();
        $bookingController->cancelBooking($id);
    }, ["put"]);

    // Update booking status (admin/employee only)
    Route::add('/bookings/([0-9]+)/status', function ($id) {
        $bookingController = new BookingController();
        $bookingController->updateBookingStatus($id);
    }, ["put"]);

    // Get booking by ID (must be last to avoid conflicts)
    Route::add('/bookings/([0-9]+)', function ($id) {
        $bookingController = new BookingController();
        $bookingController->getBooking($id);
    }, ["get"]);

    /**
     * Hotel Management Complete
     * Simple booking system without payment processing
     * Payment happens at the hotel in person
     */

    /**
     * 404 route handler
     */
    Route::pathNotFound(function () {
        ResponseService::Error("route is not defined", 404);
    });
} catch (\Throwable $error) {
    // Clear any output buffer to prevent headers already sent issues
    if (ob_get_level()) {
        ob_clean();
    }
    
    if ($_ENV["ENV"] === "LOCAL") {
        error_log("Application Error: " . $error->getMessage() . " in " . $error->getFile() . " on line " . $error->getLine());
        ResponseService::Error("A server error occurred: " . $error->getMessage(), 500);
    } else {
        error_log($error);
        ResponseService::Error("A server error occurred");
    }
}

// Run the router with /api base path for better API organization
Route::run('/api');

// End output buffering
if (ob_get_level()) {
    ob_end_flush();
}
