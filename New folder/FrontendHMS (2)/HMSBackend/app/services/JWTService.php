<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTService
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $expirationTime = 86400; // 24 hours

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'qazwsxedcrfvtgbyhnujm';
    }

    /**
     * Generate a JWT token for the user
     */
    public function generateToken(array $userData): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expirationTime;

        $payload = [
            'iss' => 'hotelFortuna',
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $userData['id'],
            'data' => [
                'id' => $userData['id'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate and decode a JWT token
     */
    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired', 401);
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Invalid token signature', 401);
        } catch (\Exception $e) {
            throw new \Exception('Invalid token', 401);
        }
    }

    /**
     * Extract user data from token
     */
    public function getUserFromToken(string $token): ?array
    {
        $decoded = $this->validateToken($token);
        return $decoded ? (array)$decoded->data : null;
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded->exp < time();
        } catch (\Exception $e) {
            return true;
        }
    }
}
