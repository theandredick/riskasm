<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public const ROLES = ['admin', 'manager', 'assessor', 'viewer'];

    // ── Lookups ───────────────────────────────────────────────────────────────

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM users WHERE email = :email',
            ['email' => strtolower(trim($email))]
        );
    }

    public static function all(string $orderBy = 'display_name ASC'): array
    {
        return Database::fetchAll("SELECT * FROM users ORDER BY $orderBy");
    }

    public static function count(): int
    {
        return (int) Database::fetchScalar('SELECT COUNT(*) FROM users');
    }

    public static function emailExists(string $email): bool
    {
        return (bool) Database::fetchScalar(
            'SELECT 1 FROM users WHERE email = :email',
            ['email' => strtolower(trim($email))]
        );
    }

    // ── Create & Update ───────────────────────────────────────────────────────

    public static function create(
        string $email,
        string $displayName,
        string $password,
        string $role = 'assessor'
    ): int {
        return Database::insert(
            'INSERT INTO users (email, display_name, password_hash, role)
             VALUES (:email, :display_name, :password_hash, :role)',
            [
                'email'         => strtolower(trim($email)),
                'display_name'  => trim($displayName),
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'role'          => $role,
            ]
        );
    }

    public static function updatePassword(int $id, string $newPassword): void
    {
        Database::execute(
            'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id',
            ['hash' => password_hash($newPassword, PASSWORD_BCRYPT), 'id' => $id]
        );
    }

    public static function touchLastLogin(int $id): void
    {
        Database::execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::execute(
            'UPDATE users SET is_active = :active, updated_at = NOW() WHERE id = :id',
            ['active' => $active ? 't' : 'f', 'id' => $id]
        );
    }

    public static function updateRole(int $id, string $role): void
    {
        Database::execute(
            'UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id',
            ['role' => $role, 'id' => $id]
        );
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    // ── Password reset tokens ─────────────────────────────────────────────────

    /**
     * Create a new password-reset token. Returns the raw token (to include in email).
     * Only the SHA-256 hash is stored in the DB.
     */
    public static function createResetToken(int $userId, int $expiryMinutes = 60): string
    {
        // Invalidate any existing tokens for this user first
        Database::execute(
            'DELETE FROM password_reset_tokens WHERE user_id = :uid',
            ['uid' => $userId]
        );

        $raw       = bin2hex(random_bytes(32));
        $hash      = hash('sha256', $raw);
        $expiresAt = date('Y-m-d H:i:sP', strtotime("+$expiryMinutes minutes"));

        Database::execute(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, :exp)',
            ['uid' => $userId, 'hash' => $hash, 'exp' => $expiresAt]
        );

        return $raw;
    }

    /**
     * Look up a valid (unexpired, unused) reset token row. Returns the row or null.
     */
    public static function findResetToken(string $rawToken): ?array
    {
        $hash = hash('sha256', $rawToken);
        return Database::fetchOne(
            'SELECT prt.*, u.email, u.display_name
             FROM password_reset_tokens prt
             JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = :hash
               AND prt.expires_at > NOW()
               AND prt.used_at IS NULL',
            ['hash' => $hash]
        );
    }

    public static function consumeResetToken(string $rawToken): void
    {
        $hash = hash('sha256', $rawToken);
        Database::execute(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = :hash',
            ['hash' => $hash]
        );
    }

    // ── Remember-me tokens ────────────────────────────────────────────────────

    /**
     * Create a 30-day remember-me token. Returns the raw token (stored in cookie).
     */
    public static function createRememberToken(int $userId): string
    {
        $raw       = bin2hex(random_bytes(32));
        $hash      = hash('sha256', $raw);
        $expiresAt = date('Y-m-d H:i:sP', strtotime('+30 days'));

        Database::execute(
            'INSERT INTO remember_tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, :exp)',
            ['uid' => $userId, 'hash' => $hash, 'exp' => $expiresAt]
        );

        return $raw;
    }

    /**
     * Look up a valid (unexpired) remember token. Returns the user row or null.
     */
    public static function findByRememberToken(string $rawToken): ?array
    {
        $hash = hash('sha256', $rawToken);
        return Database::fetchOne(
            'SELECT u.*
             FROM remember_tokens rt
             JOIN users u ON u.id = rt.user_id
             WHERE rt.token_hash = :hash
               AND rt.expires_at > NOW()
               AND u.is_active = TRUE',
            ['hash' => $hash]
        );
    }

    /**
     * Slide the expiry on an existing remember token (replace it with a fresh one).
     * Returns the new raw token.
     */
    public static function refreshRememberToken(string $oldRawToken): string
    {
        $oldHash = hash('sha256', $oldRawToken);

        $row = Database::fetchOne(
            'SELECT user_id FROM remember_tokens WHERE token_hash = :hash',
            ['hash' => $oldHash]
        );

        if ($row === null) {
            return '';
        }

        Database::execute(
            'DELETE FROM remember_tokens WHERE token_hash = :hash',
            ['hash' => $oldHash]
        );

        return self::createRememberToken((int) $row['user_id']);
    }

    public static function deleteRememberToken(string $rawToken): void
    {
        $hash = hash('sha256', $rawToken);
        Database::execute(
            'DELETE FROM remember_tokens WHERE token_hash = :hash',
            ['hash' => $hash]
        );
    }

    public static function deleteUserRememberTokens(int $userId): void
    {
        Database::execute(
            'DELETE FROM remember_tokens WHERE user_id = :uid',
            ['uid' => $userId]
        );
    }
}
