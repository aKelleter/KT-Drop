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

    public function findStatsByCategory(): array
    {
        return Database::connection()->query("
            SELECT
                COALESCE(c.name,  'Sans catégorie') AS name,
                COALESCE(c.color, '#6c757d')        AS color,
                COUNT(f.id)                         AS file_count,
                COALESCE(SUM(f.size_bytes), 0)      AS total_size
            FROM files f
            LEFT JOIN categories c ON c.id = f.category_id
            GROUP BY f.category_id
            ORDER BY file_count DESC
        ")->fetchAll();
    }

    public function findStatsBySizeRange(): array
    {
        $rows = Database::connection()->query("
            SELECT
                CASE
                    WHEN size_bytes <        102400 THEN 1
                    WHEN size_bytes <       1048576 THEN 2
                    WHEN size_bytes <      10485760 THEN 3
                    WHEN size_bytes <     104857600 THEN 4
                    ELSE                            5
                END AS sort_order,
                COUNT(*)        AS file_count,
                SUM(size_bytes) AS total_size
            FROM files
            GROUP BY sort_order
            ORDER BY sort_order ASC
        ")->fetchAll();

        $labels = [
            1 => '< 100 Ko',
            2 => '100 Ko – 1 Mo',
            3 => '1 Mo – 10 Mo',
            4 => '10 Mo – 100 Mo',
            5 => '> 100 Mo',
        ];
        $colors = [
            1 => '#4ade80',
            2 => '#60a5fa',
            3 => '#fb923c',
            4 => '#f87171',
            5 => '#c084fc',
        ];

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['sort_order']] = $row;
        }

        $result = [];
        foreach ($labels as $order => $label) {
            $result[] = [
                'label'      => $label,
                'color'      => $colors[$order],
                'file_count' => (int) ($indexed[$order]['file_count'] ?? 0),
                'total_size' => (int) ($indexed[$order]['total_size'] ?? 0),
            ];
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
