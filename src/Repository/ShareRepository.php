<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class ShareRepository
{
    public function create(array $data): void
    {
        $stmt = Database::connection()->prepare("
            INSERT INTO shares (file_id, token, expires_at, created_at, created_by)
            VALUES (:file_id, :token, :expires_at, :created_at, :created_by)
        ");

        $stmt->execute($data);
    }

    public function findByToken(string $token): array|false
    {
        $stmt = Database::connection()->prepare("
            SELECT s.*, f.original_name, f.mime_type, f.extension, f.size_bytes, f.storage_path
            FROM shares s
            INNER JOIN files f ON f.id = s.file_id
            WHERE s.token = :token
            LIMIT 1
        ");

        $stmt->execute(['token' => $token]);

        return $stmt->fetch();
    }

    public function findActiveByFileId(int $fileId): array
    {
        $stmt = Database::connection()->prepare("
            SELECT * FROM shares
            WHERE file_id = :file_id AND expires_at > :now
            ORDER BY expires_at ASC
        ");

        $stmt->execute([
            'file_id' => $fileId,
            'now' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->fetchAll();
    }

    public function findAllActive(): array
    {
        $stmt = Database::connection()->prepare("
            SELECT s.*, f.original_name, f.extension, f.size_bytes, u.email AS creator_email
            FROM shares s
            INNER JOIN files f ON f.id = s.file_id
            INNER JOIN users u ON u.id = s.created_by
            WHERE s.expires_at > :now
            ORDER BY s.expires_at ASC
        ");

        $stmt->execute(['now' => date('Y-m-d H:i:s')]);

        return $stmt->fetchAll();
    }

    public function revokeByToken(string $token): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM shares WHERE token = :token');
        $stmt->execute(['token' => $token]);
    }

    public function deleteByFileId(int $fileId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM shares WHERE file_id = :file_id');
        $stmt->execute(['file_id' => $fileId]);
    }
}
