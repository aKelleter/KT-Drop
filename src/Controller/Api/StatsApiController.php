<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\ApiResponse;
use App\Core\View;
use App\Repository\StatsRepository;

final class StatsApiController extends BaseApiController
{
    /**
     * GET /api/v1/stats
     * Admin only.
     */
    public function index(array $params): void
    {
        $user = $this->authenticate();
        $this->requireAdmin($user);

        $repo   = new StatsRepository();
        $global = $repo->findGlobal();

        ApiResponse::json([
            'files' => [
                'total'      => $global['total_files'],
                'total_size' => [
                    'bytes' => $global['total_size'],
                    'human' => View::formatBytes($global['total_size']),
                ],
                'avg_size'   => [
                    'bytes' => (int) $global['avg_size'],
                    'human' => View::formatBytes((int) $global['avg_size']),
                ],
            ],
            'shares' => [
                'active' => $global['active_shares'],
            ],
            'users' => [
                'total'   => $global['total_users'],
                'admins'  => $global['admin_count'],
                'editors' => $global['editor_count'],
            ],
            'disk' => [
                'total' => [
                    'bytes' => $global['disk_total'],
                    'human' => View::formatBytes($global['disk_total']),
                ],
                'used'  => [
                    'bytes'      => $global['disk_used'],
                    'human'      => View::formatBytes($global['disk_used']),
                    'percent'    => $global['disk_total'] > 0
                                     ? round($global['disk_used'] / $global['disk_total'] * 100, 1)
                                     : 0,
                ],
                'free'  => [
                    'bytes' => $global['disk_free'],
                    'human' => View::formatBytes($global['disk_free']),
                ],
            ],
            'by_category'   => $repo->findStatsByCategory(),
            'by_size_range' => $repo->findStatsBySizeRange(),
            'top_extensions' => $repo->findTopExtensions(),
            'top_uploaders' => $repo->findTopUploaders(),
        ]);
    }
}
