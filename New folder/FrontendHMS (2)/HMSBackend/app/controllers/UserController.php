<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\ResponseService;
use App\DTO\UserDTO;

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }    /**
     * Get all users (admin only)
     */
    public function getAllUsers()
    {
        $user = $this->authMiddleware->requireRole('admin');
        if (!$user) return;

        try {
            $filters = [];
            
            // Handle query parameters
            if (isset($_GET['role'])) {
                $filters['role'] = $_GET['role'];
            }
            
            if (isset($_GET['is_active'])) {
                $filters['is_active'] = $_GET['is_active'] === 'true';
            }
            
            if (isset($_GET['limit'])) {
                $filters['limit'] = (int)$_GET['limit'];
            }

            $users = $this->userModel->getAll($filters);
            
            // Convert to DTOs
            $userDTOs = array_map(function($userData) {
                return (new UserDTO($userData))->toArray();
            }, $users);

            ResponseService::Send($userDTOs);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve users', $e);
        }
    }    /**
     * Get a specific user
     */
    public function getUser(int $id)
    {
        $authUser = $this->authMiddleware->requireOwnership($id);
        if (!$authUser) return;

        try {
            $userData = $this->userModel->findById($id);
            
            if (!$userData) {
                $this->handleNotFound('User');
                return;
            }

            $userDTO = new UserDTO($userData);
            ResponseService::Send($userDTO->toArray());
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to retrieve user', $e);
        }
    }    /**
     * Update user information
     */
    public function updateUser(int $id)
    {
        $authUser = $this->authMiddleware->requireOwnership($id);
        if (!$authUser) return;

        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            // Validate allowed fields
            $allowedFields = ['first_name', 'last_name', 'phone'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                ResponseService::Error('No valid fields to update', 400);
                return;
            }

            $success = $this->userModel->update($id, $updateData);
            
            if (!$success) {
                $this->handleServerError('Failed to update user');
                return;
            }

            // Return updated user data
            $userData = $this->userModel->findById($id);
            $userDTO = new UserDTO($userData);
            
            ResponseService::Send([
                'message' => 'User updated successfully',
                'user' => $userDTO->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to update user', $e);
        }
    }    /**
     * Delete/deactivate user
     */
    public function deleteUser(int $id)
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
    public function changePassword(int $id)
    {
        $authUser = $this->authMiddleware->requireOwnership($id);
        if (!$authUser) return;

        try {
            $data = $this->decodePostData();
            if (!$data) return;
            
            if (!isset($data['old_password']) || !isset($data['new_password'])) {
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

            ResponseService::Send(['message' => 'Password changed successfully']);
            
        } catch (\Exception $e) {
            $this->handleServerError('Failed to change password', $e);
        }
    }
}
