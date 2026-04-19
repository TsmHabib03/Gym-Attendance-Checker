<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;

final class SettingRepository
{
    public function get(string $key, ?string $default = null): ?string
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1');
        $stmt->execute([':setting_key' => $key]);
        $row = $stmt->fetch();

        return $row['setting_value'] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, :updated_at) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)');
        $stmt->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function all(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM app_settings');

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }

        return $result;
    }
}
