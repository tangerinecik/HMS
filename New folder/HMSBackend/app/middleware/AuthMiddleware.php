<?php

namespace App\Middleware;

use App\Services\JWTService;
use App\Services\LegacyResponseAdapter as ResponseService;

class AuthMiddleware
{
    private JWTService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JWTService();
    }    /**
     * Check if the request has valid authentication
     */
    public function authenticate(): ?object
    {
        // Get authorization header using multiple methods for cross-server compatibility
        $authHeader = null;
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }
        
        if (!$authHeader) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        }
        
        if (!$authHeader) {
            ResponseService::Error('Authorization header missing', 401);
            return null;
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            ResponseService::Error('Invalid authorization header format', 401);
            return null;
        }
        
        try {
            return $this->jwtService->validateToken($matches[1]);
        } catch (\Exception $e) {
            ResponseService::Error($e->getMessage(), 401);
            return null;
        }
    }/**
     * Check if user has required role
     */
    public function requireRole(string $requiredRole): ?object
    {
        return $this->requireAnyRole([$requiredRole]);
    }/**
     * Check if user has one of the required roles
     */
    public function requireAnyRole(array $requiredRoles): ?object
    {
        $user = $this->authenticate();
        
        if (!$user) {
            return null;
        }

        // Case-insensitive role check
        foreach ($requiredRoles as $role) {
            if (strcasecmp($user->data->role, $role) === 0) {
                return $user;
            }
        }
        
        ResponseService::Error('Insufficient permissions', 403);
        return null;
    }    /**
     * Check if authenticated user is accessing their own resource
     * or has admin privileges
     */
    public function requireOwnership(int $resourceUserId): ?object
    {
        $user = $this->authenticate();
        
        if (!$user) {
            return null;
        }

        // Allow if it's the user's own resource or they're an admin
        if ($user->data->id === $resourceUserId || strcasecmp($user->data->role, 'admin') === 0) {
            return $user;
        }
        
        ResponseService::Error('Access denied - you can only access your own resources', 403);
        return null;
    }
}
