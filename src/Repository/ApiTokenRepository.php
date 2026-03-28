<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class ApiTokenRepository
{
    public function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare("
            SELECT t.id, t.user_id, t.name, t.token, t.last_used_at, t.created_at,
                   u.email, u.role
            FROM api_tokens t
            INNER JOIN users u ON u.id = t.user_id
            WHERE t.token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(): array
    {
        return Database::connection()->query("
            SELECT t.id, t.name, t.token, t.last_used_at, t.created_at,
                   u.email, u.role
            FROM api_tokens t
            INNER JOIN users u ON u.id = t.user_id
            ORDER BY t.created_at DESC
        ")->fetchAll();
    }

    public function create(int $userId, string $name, string $token): int
    {
        $stmt = Database::connection()->prepare("
            INSERT INTO api_tokens (user_id, name, token, created_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $name, $token, date('Y-m-d H:i:s')]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateLastUsed(int $id): void
    {
        Database::connection()->prepare("
            UPDATE api_tokens SET last_used_at = ? WHERE id = ?
        ")->execute([date('Y-m-d H:i:s'), $id]);
    }

    public function delete(int $id): void
    {
        Database::connection()->prepare("DELETE FROM api_tokens WHERE id = ?")->execute([$id]);
    }
}
