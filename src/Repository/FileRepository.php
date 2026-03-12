<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class FileRepository
{
    public function all(?string $search = null): array
    {
        $pdo = Database::connection();

        if ($search !== null && $search !== '') {
            $stmt = $pdo->prepare("
                SELECT f.*, u.email AS uploader_email
                FROM files f
                INNER JOIN users u ON u.id = f.uploaded_by
                WHERE
                    f.original_name LIKE :search
                    OR f.extension LIKE :search
                    OR u.email LIKE :search
                ORDER BY f.created_at DESC
            ");

            $stmt->execute([
                'search' => '%' . $search . '%',
            ]);

            return $stmt->fetchAll();
        }

        $stmt = $pdo->query("
            SELECT f.*, u.email AS uploader_email
            FROM files f
            INNER JOIN users u ON u.id = f.uploaded_by
            ORDER BY f.created_at DESC
        ");

        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = Database::connection()->prepare('SELECT * FROM files WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): void
    {
        $stmt = Database::connection()->prepare("
            INSERT INTO files (
                original_name, stored_name, mime_type, extension,
                size_bytes, sha256, storage_path, uploaded_by, created_at
            ) VALUES (
                :original_name, :stored_name, :mime_type, :extension,
                :size_bytes, :sha256, :storage_path, :uploaded_by, :created_at
            )
        ");

        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM files WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}