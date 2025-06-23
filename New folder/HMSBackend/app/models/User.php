<?php

namespace App\Models;

class User extends Model
{
    /**
     * Create a new user with email verification (default registration flow)
     */
    public function createWithVerification(string $email, string $password, string $firstName, string $lastName, ?string $phone = null, string $role = 'customer'): ?array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Check if email already exists
        if ($this->findByEmailIncludingUnverified($email)) {
            throw new \InvalidArgumentException('Email already exists');
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = bin2hex(random_bytes(32));
            $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = self::$pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, phone, role, 
                                 email_verified, email_verification_token, email_verification_expires) 
                VALUES (?, ?, ?, ?, ?, ?, false, ?, ?)
            ");
            
            $stmt->execute([
                $email, $hashedPassword, $firstName, $lastName, $phone, $role,
                $verificationToken, $verificationExpires
            ]);
            
            // Get the inserted user
            $userId = self::$pdo->lastInsertId();
            return $this->findByIdIncludingUnverified($userId);
            
        } catch (\PDOException $e) {
            error_log("User creation failed: " . $e->getMessage());
            throw new \Exception('Failed to create user');
        }
    }

    /**
     * Create a verified user (for admin use or testing)
     */
    public function create(string $email, string $password, string $firstName, string $lastName, ?string $phone = null, string $role = 'customer'): ?array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Check if email already exists
        if ($this->findByEmailIncludingUnverified($email)) {
            throw new \InvalidArgumentException('Email already exists');
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = self::$pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, phone, role, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, true)
            ");
            
            $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $role]);
            
            // Get the inserted user
            $userId = self::$pdo->lastInsertId();
            return $this->findById($userId);
            
        } catch (\PDOException $e) {
            error_log("User creation failed: " . $e->getMessage());
            throw new \Exception('Failed to create user');
        }
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): bool
    {
        try {
            $stmt = self::$pdo->prepare("
                UPDATE users 
                SET email_verified = true, 
                    email_verification_token = NULL, 
                    email_verification_expires = NULL 
                WHERE email_verification_token = ? 
                AND email_verification_expires > NOW()
            ");
            
            $stmt->execute([$token]);
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Email verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate new verification token
     */
    public function generateVerificationToken(string $email): ?string
    {
        try {
            $verificationToken = bin2hex(random_bytes(32));
            $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = self::$pdo->prepare("
                UPDATE users 
                SET email_verification_token = ?, 
                    email_verification_expires = ? 
                WHERE email = ? AND email_verified = false
            ");
            
            $stmt->execute([$verificationToken, $verificationExpires, $email]);
            
            return $stmt->rowCount() > 0 ? $verificationToken : null;
            
        } catch (\PDOException $e) {
            error_log("Token generation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find user by email (only verified users)
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = self::$pdo->prepare("
            SELECT id, email, first_name, last_name, phone, role, is_active, 
                   email_verified, created_at, password_hash
            FROM users 
            WHERE email = ? AND is_active = true AND email_verified = true
        ");
        $stmt->execute([$email]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find user by email including unverified users
     */
    public function findByEmailIncludingUnverified(string $email): ?array
    {
        $stmt = self::$pdo->prepare("
            SELECT id, email, first_name, last_name, phone, role, is_active, 
                   email_verified, created_at, password_hash
            FROM users 
            WHERE email = ? AND is_active = true
        ");
        $stmt->execute([$email]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find user by ID (only verified users)
     */
    public function findById(int $id): ?array
    {
        $stmt = self::$pdo->prepare("
            SELECT id, email, first_name, last_name, phone, role, is_active, 
                   email_verified, created_at
            FROM users 
            WHERE id = ? AND is_active = true AND email_verified = true
        ");
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find user by ID including unverified users
     */
    public function findByIdIncludingUnverified(int $id): ?array
    {
        $stmt = self::$pdo->prepare("
            SELECT id, email, first_name, last_name, phone, role, is_active, 
                   email_verified, created_at, email_verification_token
            FROM users 
            WHERE id = ? AND is_active = true
        ");
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update user information
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['first_name', 'last_name', 'phone'];
        $updates = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Change user password
     */
    public function changePassword(int $id, string $oldPassword, string $newPassword): bool
    {
        // First verify the old password
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }

        $stmt = self::$pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result || !password_verify($oldPassword, $result['password_hash'])) {
            return false;
        }

        // Update with new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = self::$pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        return $stmt->execute([$hashedPassword, $id]);
    }

    /**
     * Deactivate user account
     */
    public function deactivate(int $id): bool
    {
        $stmt = self::$pdo->prepare("UPDATE users SET is_active = false WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all users with optional filters
     */
    public function getAll(array $filters = []): array
    {
        $whereConditions = ['is_active = true'];
        $params = [];

        if (!empty($filters['role'])) {
            $whereConditions[] = 'role = ?';
            $params[] = $filters['role'];
        }

        if (isset($filters['email_verified'])) {
            $whereConditions[] = 'email_verified = ?';
            $params[] = $filters['email_verified'] ? 1 : 0;
        }

        $whereClause = implode(' AND ', $whereConditions);

        $stmt = self::$pdo->prepare("
            SELECT id, email, first_name, last_name, phone, role, is_active, 
                   email_verified, created_at
            FROM users 
            WHERE $whereClause 
            ORDER BY created_at DESC
        ");

        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
