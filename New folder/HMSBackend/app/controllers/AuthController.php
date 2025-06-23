<?php

namespace App\Controllers;

use App\Services\Interfaces\ResponseServiceInterface;
use App\Services\LoggerService;
use App\Services\JWTService;
use App\Services\EmailService;
use App\Middleware\AuthMiddleware;
use App\Models\User;
use App\DTO\Auth\{LoginRequestDTO, RegisterRequestDTO, EmailVerificationRequestDTO, ResendVerificationRequestDTO, AuthResponseDTO};
use App\DTO\UserDTO;

/**
 * Authentication Controller - Refactored with Dependency Injection
 * Demonstrates proper OOP practices, SOLID principles, and clean architecture
 */
class AuthController extends Controller
{
    private User $userModel;
    private EmailService $emailService;
    private LoggerService $logger;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = $this->container->resolve('user_model');
        $this->emailService = $this->container->resolve('email_service');
        $this->logger = $this->container->resolve('logger');
    }

    /**
     * User registration with email verification
     */
    public function register(): void
    {
        try {
            $requestData = $this->getRequestData();
            if (!$requestData) return;

            $registerDTO = RegisterRequestDTO::fromArray($requestData);
            $validationErrors = $registerDTO->validate();

            if (!empty($validationErrors)) {
                $this->responseService->sendValidationError($validationErrors);
                return;
            }

            // Check if user already exists
            $existingUser = $this->userModel->findByEmailIncludingUnverified($registerDTO->email);
            if ($existingUser) {
                $this->responseService->sendError('Email already registered', 409);
                return;
            }

            // Create user with email verification
            $newUser = $this->userModel->createWithVerification(
                $registerDTO->email,
                $registerDTO->password,
                $registerDTO->firstName,
                $registerDTO->lastName,
                $registerDTO->phone,
                $registerDTO->role
            );            if (!$newUser) {
                $this->responseService->sendError('Failed to create user', 500);
                return;
            }

            // Send verification email
            $emailSent = $this->emailService->sendVerificationEmail(
                $newUser['email'],
                $newUser['first_name'],
                $newUser['email_verification_token']
            );

            if (!$emailSent) {
                $this->logger->logError('Failed to send verification email', 500, [
                    'user_id' => $newUser['id'],
                    'email' => $newUser['email']
                ]);
                // Don't fail registration if email fails, just log it
            }

            $this->logger->logInfo('User registered', [
                'user_id' => $newUser['id'],
                'email' => $newUser['email'],
                'role' => $newUser['role'],
                'email_sent' => $emailSent
            ]);

            $this->responseService->sendSuccess([
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user_id' => $newUser['id']
            ], 201);

        } catch (\Exception $e) {
            $this->logger->logError('Registration failed', 500, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->responseService->sendError('Registration failed', 500);
        }
    }

    /**
     * User login with JWT token generation
     */
    public function login(): void
    {
        try {
            $requestData = $this->getRequestData();
            if (!$requestData) return;

            $loginDTO = LoginRequestDTO::fromArray($requestData);
            $validationErrors = $loginDTO->validate();

            if (!empty($validationErrors)) {
                $this->responseService->sendValidationError($validationErrors);
                return;
            }

            // Find user and verify credentials
            $user = $this->userModel->findByEmail($loginDTO->email);
            if (!$user || !password_verify($loginDTO->password, $user['password_hash'])) {
                $this->responseService->sendError('Invalid credentials', 401);
                return;
            }

            // Check if user is active
            if (!$user['is_active']) {
                $this->responseService->sendError('Account is deactivated', 403);
                return;
            }

            // Check if email is verified
            if (!$user['email_verified']) {
                $this->responseService->sendError('Please verify your email before logging in', 403);
                return;
            }

            // Generate JWT token
            $token = $this->jwtService->generateToken($user);
            $userDTO = UserDTO::fromArray($user);
            $authResponse = new AuthResponseDTO($token, $userDTO);

            $this->logger->logInfo('User logged in', [
                'user_id' => $user['id'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $this->responseService->sendSuccess($authResponse->toArray());

        } catch (\Exception $e) {
            $this->logger->logError('Login failed', 500, [
                'error' => $e->getMessage()
            ]);
            $this->responseService->sendError('Login failed', 500);
        }
    }

    /**
     * Email verification
     */
    public function verifyEmail(): void
    {
        try {
            $requestData = $this->getRequestData();
            if (!$requestData) return;

            $verificationDTO = EmailVerificationRequestDTO::fromArray($requestData);
            $validationErrors = $verificationDTO->validate();

            if (!empty($validationErrors)) {
                $this->responseService->sendValidationError($validationErrors);
                return;
            }

            $success = $this->userModel->verifyEmail($verificationDTO->token);
            
            if ($success) {
                $this->logger->logInfo('Email verified', ['token' => $verificationDTO->token]);
                $this->responseService->sendSuccess(['message' => 'Email verified successfully']);
            } else {
                $this->responseService->sendError('Invalid or expired verification token', 400);
            }

        } catch (\Exception $e) {
            $this->logger->logError('Email verification failed', 500, [
                'error' => $e->getMessage()
            ]);
            $this->responseService->sendError('Email verification failed', 500);
        }
    }

    /**
     * Get current authenticated user information
     */
    public function me(): void
    {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) return; // Error already sent by middleware

            $userData = $this->userModel->findById($user->data->id);
            if (!$userData) {
                $this->responseService->sendError('User not found', 404);
                return;
            }

            $userDTO = UserDTO::fromArray($userData);
            $this->responseService->sendSuccess($userDTO->toArray());

        } catch (\Exception $e) {
            $this->logger->logError('Failed to get user info', 500, [
                'error' => $e->getMessage()
            ]);
            $this->responseService->sendError('Failed to get user information', 500);
        }
    }

    /**
     * Resend email verification
     */
    public function resendVerification(): void
    {
        try {
            $requestData = $this->getRequestData();
            if (!$requestData) return;

            $resendDTO = ResendVerificationRequestDTO::fromArray($requestData);
            $validationErrors = $resendDTO->validate();

            if (!empty($validationErrors)) {
                $this->responseService->sendValidationError($validationErrors);
                return;
            }            $token = $this->userModel->generateVerificationToken($resendDTO->email);
            
            if ($token) {
                // Get user data to send email
                $userData = $this->userModel->findByEmailIncludingUnverified($resendDTO->email);
                
                if ($userData) {
                    // Send verification email
                    $emailSent = $this->emailService->sendVerificationEmail(
                        $userData['email'],
                        $userData['first_name'],
                        $token
                    );

                    if (!$emailSent) {
                        $this->logger->logError('Failed to send verification email', 500, [
                            'email' => $resendDTO->email
                        ]);
                    }

                    $this->logger->logInfo('Verification email resent', [
                        'email' => $resendDTO->email,
                        'email_sent' => $emailSent
                    ]);
                }
                
                $this->responseService->sendSuccess(['message' => 'Verification email sent']);
            } else {
                $this->responseService->sendError('Email not found or already verified', 400);
            }

        } catch (\Exception $e) {
            $this->logger->logError('Failed to resend verification', 500, [
                'error' => $e->getMessage()
            ]);
            $this->responseService->sendError('Failed to resend verification email', 500);
        }
    }

    /**
     * Logout (token invalidation would be handled client-side or with a token blacklist)
     */
    public function logout(): void
    {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) return;

            $this->logger->logInfo('User logged out', ['user_id' => $user->data->id]);
            $this->responseService->sendSuccess(['message' => 'Logged out successfully']);

        } catch (\Exception $e) {
            $this->logger->logError('Logout failed', 500, [
                'error' => $e->getMessage()
            ]);
            $this->responseService->sendError('Logout failed', 500);
        }
    }

    /**
     * Refresh token endpoint
     * Since JWTs are stateless, this could be used to issue new tokens
     */
    public function refresh(): void
    {
        $user = $this->authMiddleware->authenticate();
        if (!$user) return;

        try {
            // Generate new token
            $newToken = $this->jwtService->generateToken([
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            $authResponse = new AuthResponseDTO(
                $newToken,
                UserDTO::fromArray((array)$user),
                'Token refreshed successfully'
            );

            $this->responseService->sendSuccess($authResponse->toArray());
        } catch (\Exception $e) {
            $this->logger->logError('Token refresh failed', 500, ['error' => $e->getMessage()]);
            $this->responseService->sendError('Token refresh failed', 500);
        }
    }
}
