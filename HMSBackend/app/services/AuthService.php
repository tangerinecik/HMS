<?php

namespace App\Services;

use App\Models\User;
use App\DTO\Auth\RegisterRequestDTO;
use App\DTO\Auth\LoginRequestDTO;
use App\DTO\Auth\EmailVerificationRequestDTO;
use App\DTO\Auth\ResendVerificationRequestDTO;
use App\DTO\Auth\AuthResponseDTO;
use App\DTO\UserDTO;

class AuthService extends BaseService
{
    private User $userModel;
    private EmailService $emailService;
    private JWTService $jwtService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->emailService = new EmailService();
        $this->jwtService = new JWTService();
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $registerDTO = RegisterRequestDTO::fromArray($data);
        $errors = $this->validateDTO($registerDTO);
        
        if ($this->hasValidationErrors($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if user already exists
        $existingUser = $this->userModel->findByEmailIncludingUnverified($registerDTO->email);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        try {
            // Create user with verification
            $userData = $this->userModel->createWithVerification(
                $registerDTO->email,
                $registerDTO->password,
                $registerDTO->firstName,
                $registerDTO->lastName,
                $registerDTO->phone,
                $registerDTO->role
            );

            if (!$userData) {
                return ['success' => false, 'message' => 'Failed to create user'];
            }

            // Send verification email
            $emailSent = $this->emailService->sendVerificationEmail(
                $userData['email'],
                $userData['first_name'],
                $userData['email_verification_token']
            );

            // Remove sensitive data
            unset($userData['email_verification_token']);

            return [
                'success' => true,
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user' => (new UserDTO($userData))->toArray(),
                'email_sent' => $emailSent
            ];

        } catch (\Exception $e) {
            $this->logError('Registration failed', $e);
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    /**
     * Login user
     */
    public function login(array $data): array
    {
        $loginDTO = LoginRequestDTO::fromArray($data);
        $errors = $this->validateDTO($loginDTO);
        
        if ($this->hasValidationErrors($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Find user by email
            $userData = $this->userModel->findByEmail($loginDTO->email);
            
            if (!$userData) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            if (!password_verify($loginDTO->password, $userData['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Remove password hash from user data
            unset($userData['password_hash']);

            // Generate JWT token
            $token = $this->jwtService->generateToken($userData);
            $userDTO = new UserDTO($userData);
            $authResponse = new AuthResponseDTO($token, $userDTO, 'Login successful');

            return ['success' => true, 'data' => $authResponse->toArray()];

        } catch (\Exception $e) {
            $this->logError('Login failed', $e);
            return ['success' => false, 'message' => 'Login failed'];
        }
    }

    /**
     * Verify email
     */
    public function verifyEmail(array $data): array
    {
        $verificationDTO = EmailVerificationRequestDTO::fromArray($data);
        $errors = $this->validateDTO($verificationDTO);
        
        if ($this->hasValidationErrors($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $success = $this->userModel->verifyEmail($verificationDTO->token);
            
            if (!$success) {
                return ['success' => false, 'message' => 'Invalid or expired verification token'];
            }

            return ['success' => true, 'message' => 'Email verified successfully. You can now log in.'];

        } catch (\Exception $e) {
            $this->logError('Email verification failed', $e);
            return ['success' => false, 'message' => 'Email verification failed'];
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification(array $data): array
    {
        $resendDTO = ResendVerificationRequestDTO::fromArray($data);
        $errors = $this->validateDTO($resendDTO);
        
        if ($this->hasValidationErrors($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Check if user exists and is unverified
            $userData = $this->userModel->findByEmailIncludingUnverified($resendDTO->email);
            
            if (!$userData) {
                return ['success' => false, 'message' => 'User not found'];
            }

            if ($userData['email_verified']) {
                return ['success' => false, 'message' => 'Email is already verified'];
            }

            // Generate new verification token
            $verificationToken = $this->userModel->generateVerificationToken($resendDTO->email);
            
            if (!$verificationToken) {
                return ['success' => false, 'message' => 'Failed to generate verification token'];
            }

            // Send verification email
            $emailSent = $this->emailService->sendVerificationEmail(
                $userData['email'],
                $userData['first_name'],
                $verificationToken
            );

            return [
                'success' => true,
                'message' => 'Verification email sent successfully.',
                'email_sent' => $emailSent
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to resend verification email', $e);
            return ['success' => false, 'message' => 'Failed to resend verification email'];
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken(array $userData): array
    {
        try {
            $token = $this->jwtService->generateToken($userData);
            $userDTO = new UserDTO($userData);
            $authResponse = new AuthResponseDTO($token, $userDTO, 'Token refreshed successfully');

            return ['success' => true, 'data' => $authResponse->toArray()];

        } catch (\Exception $e) {
            $this->logError('Failed to refresh token', $e);
            return ['success' => false, 'message' => 'Failed to refresh token'];
        }
    }
}
