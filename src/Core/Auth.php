<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class Auth
{
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
    }

    public static function attemptLogin(string $username, string $password): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return false;
        }

        $hash = (string) $admin['password_hash'];
        $valid = false;

        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2')) {
            $valid = password_verify($password, $hash);
        } else {
            $valid = hash_equals($hash, $password);
            if ($valid) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $update = $pdo->prepare('UPDATE admins SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
                $update->execute([
                    ':password_hash' => $newHash,
                    ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ':id' => $admin['id'],
                ]);
            }
        }

        if (!$valid) {
            return false;
        }

        Session::regenerate();
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = (string) $admin['username'];

        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
