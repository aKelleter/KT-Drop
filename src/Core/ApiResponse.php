<?php
declare(strict_types=1);

namespace App\Core;

final class ApiResponse
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    public static function paginated(array $items, int $total, int $page, int $perPage): never
    {
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'data' => $items,
            'meta' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $totalPages,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    public static function error(string $message, int $status = 400): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $message, 'code' => $status], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }
}
