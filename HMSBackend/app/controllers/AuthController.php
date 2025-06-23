<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\ResponseService;
use App\Services\EmailService;
use App\DTO\Auth\RegisterRequestDTO;
use App\DTO\Auth\LoginRequestDTO;
use App\DTO\Auth\AuthResponseDTO;
use App\DTO\UserDTO;
use App\DTO\Auth\EmailVerificationRequestDTO;
use App\DTO\Auth\ResendVerificationRequestDTO;

class AuthController extends Controller
{
    private User $userModel;
    private EmailService $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->emailService = new EmailService();
    }    /**
     * Register a new user with email verification
     */
    public function register()
    {
        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            $registerDTO = new RegisterRequestDTO($data);
            
            // Handle validation errors
            if ($this->handleValidationErrors($registerDTO->validate())) {
                return;
            }
            
            // Create the user with verification token
            $userData = $this->userModel->createWithVerification(
                $registerDTO->email,
                $registerDTO->password,
                $registerDTO->firstName,
                $registerDTO->lastName,
                $registerDTO->phone,
                $registerDTO->role
            );
            
            if (!$userData) {
                $this->handleServerError('Failed to create user');
                return;
            }
            
            // Send verification email
            $emailSent = $this->emailService->sendVerificationEmail(
                $userData['email'],
                $userData['first_name'],
                $userData['email_verification_token']
            );
            
            if (!$emailSent) {
                error_log('Failed to send verification email to: ' . $userData['email']);
            }
            
            // Remove sensitive data
            unset($userData['email_verification_token']);
            
            // Return success message without JWT token (user needs to verify first)
            ResponseService::Send([
                'message' => 'Registration successful. Please check your email to verify your account.',
                'email_sent' => $emailSent,
                'user' => (new UserDTO($userData))->toArray()
            ], 201);
            
        } catch (\InvalidArgumentException $e) {
            ResponseService::Error($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->handleServerError('Registration failed', $e);
        }
    }/**
     * Login an existing user (only verified users can login)
     */
    public function login()
    {
        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            $loginDTO = new LoginRequestDTO($data);
            
            // Handle validation errors
            if ($this->handleValidationErrors($loginDTO->validate())) {
                return;
            }
            
            // Find user by email (only verified users)
            $userData = $this->userModel->findByEmail($loginDTO->email);
            
            if (!$userData) {
                // Check if user exists but is unverified
                $unverifiedUser = $this->userModel->findByEmailIncludingUnverified($loginDTO->email);
                if ($unverifiedUser && !$unverifiedUser['email_verified']) {
                    ResponseService::Error('Please verify your email address before logging in', 401);
                    return;
                }
                
                ResponseService::Error('Invalid credentials', 401);
                return;
            }
            
            if (!password_verify($loginDTO->password, $userData['password_hash'])) {
                ResponseService::Error('Invalid credentials', 401);
                return;
            }
            
            // Remove password hash from user data
            unset($userData['password_hash']);
            
            // Generate JWT token and send response
            $token = $this->jwtService->generateToken($userData);
            $userDTO = new UserDTO($userData);
            $authResponse = new AuthResponseDTO($token, $userDTO, 'Login successful');
            
            ResponseService::Send($authResponse->toArray(), 200);
            
        } catch (\Exception $e) {
            $this->handleServerError('Login failed', $e);
        }
    }/**
     * Get the authenticated user's profile
     */
    public function me()
    {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) return;
            
            // Get fresh user data from database
            $userData = $this->userModel->findById($user->data->id);
            
            if (!$userData) {
                $this->handleNotFound('User');
                return;
            }
            
            $userDTO = new UserDTO($userData);
            ResponseService::Send($userDTO->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to get user data', $e);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) return;
            
            // Get fresh user data and generate new token
            $userData = $this->userModel->findById($user->data->id);
            
            if (!$userData) {
                $this->handleNotFound('User');
                return;
            }
            
            $token = $this->jwtService->generateToken($userData);
            $userDTO = new UserDTO($userData);
            $authResponse = new AuthResponseDTO($token, $userDTO, 'Token refreshed successfully');
            
            ResponseService::Send($authResponse->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to refresh token', $e);
        }
    }

    /**
     * Logout (placeholder - with JWT, logout is typically handled client-side)
     */
    public function logout()
    {
        // With JWT, logout is typically handled client-side by removing the token
        // However, you could implement a token blacklist here if needed
        ResponseService::Send(['message' => 'Logout successful'], 200);
    }

    /**
     * Verify email address
     */
    public function verifyEmail()
    {
        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            $verificationDTO = new EmailVerificationRequestDTO($data);
            
            // Handle validation errors
            if ($this->handleValidationErrors($verificationDTO->validate())) {
                return;
            }
            
            $success = $this->userModel->verifyEmail($verificationDTO->token);
            
            if (!$success) {
                ResponseService::Error('Invalid or expired verification token', 400);
                return;
            }
            
            ResponseService::Send([
                'message' => 'Email verified successfully. You can now log in.'
            ]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Email verification failed', $e);
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification()
    {
        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            $resendDTO = new ResendVerificationRequestDTO($data);
            
            // Handle validation errors
            if ($this->handleValidationErrors($resendDTO->validate())) {
                return;
            }
            
            // Check if user exists and is unverified
            $userData = $this->userModel->findByEmailIncludingUnverified($resendDTO->email);
            
            if (!$userData) {
                ResponseService::Error('User not found', 404);
                return;
            }
            
            if ($userData['email_verified']) {
                ResponseService::Error('Email is already verified', 400);
                return;
            }
            
            // Generate new verification token
            $verificationToken = $this->userModel->generateVerificationToken($resendDTO->email);
            
            if (!$verificationToken) {
                ResponseService::Error('Failed to generate verification token', 500);
                return;
            }
            
            // Send verification email
            $emailSent = $this->emailService->sendVerificationEmail(
                $userData['email'],
                $userData['first_name'],
                $verificationToken
            );
            
            if (!$emailSent) {
                error_log('Failed to send verification email to: ' . $userData['email']);
            }
            
            ResponseService::Send([
                'message' => 'Verification email sent successfully.',
                'email_sent' => $emailSent
            ]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to resend verification email', $e);
        }
    }
}
