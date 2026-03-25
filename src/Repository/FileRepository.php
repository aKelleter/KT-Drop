<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

final class FileRepository
{
    public function countAll(?string $search = null, ?int $categoryId = null): int
    {
        $pdo = Database::connection();

        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = "(f.original_name LIKE :search OR f.extension LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($categoryId !== null) {
            $where[] = "f.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        $sql = "
            SELECT COUNT(*)
            FROM files f
            INNER JOIN users u ON u.id = f.uploaded_by
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function findPaginated(int $limit, int $offset, ?string $search = null, ?int $categoryId = null): array
    {
        $pdo = Database::connection();

        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = "(f.original_name LIKE :search OR f.extension LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($categoryId !== null) {
            $where[] = "f.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        $sql = "
            SELECT f.*, u.email AS uploader_email,
                   c.name AS category_name, c.color AS category_color
            FROM files f
            INNER JOIN users u ON u.id = f.uploaded_by
            LEFT JOIN categories c ON c.id = f.category_id
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY f.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function all(?string $search = null, ?int $categoryId = null): array
    {
        return $this->findPaginated(PHP_INT_MAX, 0, $search, $categoryId);
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
                size_bytes, sha256, storage_path, uploaded_by, created_at, category_id
            ) VALUES (
                :original_name, :stored_name, :mime_type, :extension,
                :size_bytes, :sha256, :storage_path, :uploaded_by, :created_at, :category_id
            )
        ");

        $stmt->execute($data);
    }

    public function update(int $id, string $originalName, ?int $categoryId): void
    {
        Database::connection()->prepare("
            UPDATE files SET original_name = :original_name, category_id = :category_id WHERE id = :id
        ")->execute([
            'original_name' => $originalName,
            'category_id'   => $categoryId,
            'id'            => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM files WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
