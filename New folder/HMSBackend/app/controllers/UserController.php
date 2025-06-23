<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\LegacyResponseAdapter as ResponseService;
use App\Services\EmailService;
use App\DTO\UserDTO;

class UserController extends Controller
{
    private User $userModel;
    private EmailService $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->emailService = new EmailService();
    }

    /**
     * Get all users (admin only)
     */
    public function getAllUsers(): void
    {
        $user = $this->authMiddleware->requireRole('admin');
        if (!$user) return;

        try {
            $filters = $this->extractFilters(['role', 'is_active', 'limit']);
            $users = $this->userModel->getAll($filters);
            ResponseService::Send($this->mapToDTOs($users, UserDTO::class));
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve users', $e);
        }
    }

    /**
     * Get a specific user
     */
    public function getUser(int $id): void
    {
        $authUser = $this->authMiddleware->requireOwnership($id);
        if (!$authUser) return;

        try {
            $userData = $this->userModel->findById($id);
            if (!$userData) {
                $this->handleNotFound('User');
                return;
            }

            ResponseService::Send(UserDTO::fromArray($userData)->toArray());
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve user', $e);
        }
    }    /**
     * Update user information
     */
    public function updateUser(int $id): void
    {
        $authUser = $this->authMiddleware->requireOwnership($id);
        if (!$authUser) return;

        try {
            $data = $this->getRequestData();
            if (!$data) return;

            $updateData = $this->filterAllowedFields($data, ['first_name', 'last_name', 'phone']);
            if (empty($updateData)) {
                ResponseService::Error('No valid fields to update', 400);
                return;
            }            $success = $this->userModel->update($id, $updateData);
            if (!$success) {
                ResponseService::Error('Failed to update user', 500);
                return;
            }

            $userData = $this->userModel->findById($id);
            ResponseService::Send([
                'message' => 'User updated successfully',
                'user' => UserDTO::fromArray($userData)->toArray()
            ]);
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update user', $e);
        }
    }

    /**
     * Delete/deactivate user
     */
    public function deleteUser(int $id): void
    {
        $authUser = $this->authMiddleware->requireOwnership($id);
        if (!$authUser) return;

        try {
            $success = $this->userModel->deactivate($id);
            if (!$success) {
                $this->handleServerError('Failed to deactivate user');
                return;
            }

            ResponseService::Send(['message' => 'User account deactivated successfully']);
        } catch (\Exception $e) {
            $this->handleServerError('Failed to deactivate user', $e);
        }
    }    /**
     * Change user password
     */    
    public function changePassword(int $id): void
    {
        $authUser = $this->authMiddleware->requireOwnership($id);
        if (!$authUser) return;

        try {
            $data = $this->getRequestData();
            if (!$data) return;

            if (!isset($data['old_password'], $data['new_password'])) {
                ResponseService::Error('Old password and new password are required', 400);
                return;
            }

            if (strlen($data['new_password']) < 8) {
                ResponseService::Error('New password must be at least 8 characters long', 400);
                return;
            }

            $success = $this->userModel->changePassword($id, $data['old_password'], $data['new_password']);
            if (!$success) {
                ResponseService::Error('Failed to change password. Please check your old password.', 400);
                return;
            }

            // Send email notification
            $userData = $this->userModel->findById($id);
            if ($userData) {
                $this->emailService->sendPasswordChangeConfirmation(
                    $userData['email'],
                    $userData['first_name']
                );
            }

            ResponseService::Send(['message' => 'Password changed successfully']);
        } catch (\Exception $e) {
            $this->handleServerError('Failed to change password', $e);
        }
    }
}
