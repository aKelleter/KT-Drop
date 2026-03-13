<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

final class FileRepository
{
    public function countAll(?string $search = null): int
    {
        $pdo = Database::connection();

        if ($search !== null && $search !== '') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM files f
                INNER JOIN users u ON u.id = f.uploaded_by
                WHERE
                    f.original_name LIKE :search
                    OR f.extension LIKE :search
                    OR u.email LIKE :search
            ");

            $stmt->execute([
                'search' => '%' . $search . '%',
            ]);

            return (int) $stmt->fetchColumn();
        }

        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM files f
            INNER JOIN users u ON u.id = f.uploaded_by
        ");

        return (int) $stmt->fetchColumn();
    }

    public function findPaginated(int $limit, int $offset, ?string $search = null): array
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
                LIMIT :limit OFFSET :offset
            ");

            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        }

        $stmt = $pdo->prepare("
            SELECT f.*, u.email AS uploader_email
            FROM files f
            INNER JOIN users u ON u.id = f.uploaded_by
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function all(?string $search = null): array
    {
        return $this->findPaginated(PHP_INT_MAX, 0, $search);
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