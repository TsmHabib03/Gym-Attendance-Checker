<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class Auth
{
    /**
     * Dummy bcrypt hash used to keep failed-login timing constant
     * regardless of whether the username exists.
     */
    private const TIMING_SAFE_DUMMY_HASH = '$2y$12$abcdefghijklmnopqrstuuMQ5yQ8d8H9E1r3M9e0nq6zL5g8b2cZ8e';

    public static function id(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    public static function user(): ?array
    {
        if (!isset($_SESSION['admin_id'])) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['admin_id'],
            'username' => (string) ($_SESSION['admin_username'] ?? ''),
        ];
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in first.');
            redirect('/login');
        }

        // Defense-in-depth: re-validate session binding on every protected
        // request so a stolen cookie alone is not enough.
        if (!Session::isFingerprintValid()) {
            Logger::audit('session_fingerprint_mismatch', self::id(), [
                'ip' => Request::ip(),
            ]);
            self::logout();
            flash('error', 'Your session has expired. Please sign in again.');
            redirect('/login');
        }
    }

    public static function attemptLogin(string $username, string $password): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if (!$admin) {
            // Run a dummy verify to keep timing roughly constant whether or not
            // the username exists, mitigating user enumeration via timing.
            password_verify($password, self::TIMING_SAFE_DUMMY_HASH);
            return false;
        }

        $hash = (string) $admin['password_hash'];

        // SECURITY: Only accept modern, salted password hashes. Plaintext or
        // legacy formats are rejected — operators must reset the password.
        if (!str_starts_with($hash, '$2y$') && !str_starts_with($hash, '$argon2')) {
            Logger::error('Refusing to authenticate against non-bcrypt/argon2 hash', [
                'admin_id' => (int) $admin['id'],
            ]);
            password_verify($password, self::TIMING_SAFE_DUMMY_HASH);
            return false;
        }

        if (!password_verify($password, $hash)) {
            return false;
        }

        // Transparently rehash on login if the cost has been raised.
        if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
            try {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                if (is_string($newHash)) {
                    $update = $pdo->prepare('UPDATE admins SET password_hash = :h, updated_at = :u WHERE id = :id');
                    $update->execute([
                        ':h' => $newHash,
                        ':u' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                        ':id' => (int) $admin['id'],
                    ]);
                }
            } catch (\Throwable $t) {
                Logger::error('Failed to rehash admin password', ['admin_id' => (int) $admin['id']]);
            }
        }

        Session::regenerate();
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = (string) $admin['username'];
        $_SESSION['login_at'] = time();
        Session::bindFingerprint();

        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
