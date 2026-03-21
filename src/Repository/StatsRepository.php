<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class StatsRepository
{
    public function findGlobal(): array
    {
        $pdo = Database::connection();

        $files = $pdo->query("
            SELECT
                COUNT(*)        AS total_files,
                COALESCE(SUM(size_bytes), 0) AS total_size,
                COALESCE(AVG(size_bytes), 0) AS avg_size
            FROM files
        ")->fetch();

        $shares = $pdo->prepare("
            SELECT COUNT(*) AS active_shares
            FROM shares
            WHERE expires_at > :now
        ");
        $shares->execute(['now' => date('Y-m-d H:i:s')]);
        $sharesRow = $shares->fetch();

        $users = $pdo->query("
            SELECT
                COUNT(*)                                  AS total_users,
                SUM(CASE WHEN role = 'admin'  THEN 1 ELSE 0 END) AS admin_count,
                SUM(CASE WHEN role = 'editor' THEN 1 ELSE 0 END) AS editor_count
            FROM users
        ")->fetch();

        return [
            'total_files'   => (int)   ($files['total_files']     ?? 0),
            'total_size'    => (int)   ($files['total_size']       ?? 0),
            'avg_size'      => (float) ($files['avg_size']         ?? 0),
            'active_shares' => (int)   ($sharesRow['active_shares'] ?? 0),
            'total_users'   => (int)   ($users['total_users']      ?? 0),
            'admin_count'   => (int)   ($users['admin_count']      ?? 0),
            'editor_count'  => (int)   ($users['editor_count']     ?? 0),
        ];
    }

    public function findTopExtensions(int $limit = 8): array
    {
        $stmt = Database::connection()->prepare("
            SELECT
                LOWER(COALESCE(extension, 'inconnu')) AS extension,
                COUNT(*)        AS file_count,
                SUM(size_bytes) AS total_size
            FROM files
            GROUP BY LOWER(extension)
            ORDER BY file_count DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findUploadsPerDay(int $days = 30): array
    {
        $stmt = Database::connection()->prepare("
            SELECT
                DATE(created_at) AS day,
                COUNT(*)         AS file_count
            FROM files
            WHERE created_at >= DATE('now', :offset)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");

        $stmt->execute(['offset' => '-' . $days . ' days']);

        $rows = $stmt->fetchAll();

        // Fill in missing days with 0
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['day']] = (int) $row['file_count'];
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $result[] = ['day' => $day, 'file_count' => $indexed[$day] ?? 0];
        }

        return $result;
    }

    public function findTopUploaders(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare("
            SELECT
                u.email,
                COUNT(f.id)          AS file_count,
                COALESCE(SUM(f.size_bytes), 0) AS total_size
            FROM users u
            LEFT JOIN files f ON f.uploaded_by = u.id
            GROUP BY u.id
            ORDER BY file_count DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
