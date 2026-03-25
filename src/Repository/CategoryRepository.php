<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class CategoryRepository
{
    public function findAll(): array
    {
        return Database::connection()
            ->query("SELECT * FROM categories ORDER BY name ASC")
            ->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = Database::connection()->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(string $name, string $color): void
    {
        Database::connection()->prepare("
            INSERT INTO categories (name, color, created_at)
            VALUES (:name, :color, :created_at)
        ")->execute([
            'name'       => $name,
            'color'      => $color,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(int $id, string $name, string $color): void
    {
        Database::connection()->prepare("
            UPDATE categories SET name = :name, color = :color WHERE id = :id
        ")->execute(['name' => $name, 'color' => $color, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $pdo = Database::connection();
        $pdo->prepare("UPDATE files SET category_id = NULL WHERE category_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM categories WHERE id = :id")->execute(['id' => $id]);
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $pdo = Database::connection();

        if ($excludeId !== null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = :name AND id != :id");
            $stmt->execute(['name' => $name, 'id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = :name");
            $stmt->execute(['name' => $name]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }
}
