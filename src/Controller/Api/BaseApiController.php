<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\ApiResponse;
use App\Repository\ApiTokenRepository;

abstract class BaseApiController
{
    /**
     * Validates the Bearer token and returns the associated user row.
     * Calls ApiResponse::error() (which exits) on failure.
     */
    protected function authenticate(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? '';

        // Fallback via getallheaders() (CGI/FastCGI)
        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!str_starts_with($header, 'Bearer ')) {
            ApiResponse::error('Token d\'authentification manquant', 401);
        }

        $token = substr($header, 7);
        $repo  = new ApiTokenRepository();
        $user  = $repo->findByToken($token);

        if ($user === null) {
            ApiResponse::error('Token invalide ou révoqué', 401);
        }

        $repo->updateLastUsed((int) $user['id']);

        return $user;
    }

    /**
     * Aborts with 403 if the authenticated user is not an admin.
     */
    protected function requireAdmin(array $user): void
    {
        if ($user['role'] !== 'admin') {
            ApiResponse::error('Accès réservé aux administrateurs', 403);
        }
    }

    /**
     * Returns the parsed request body (JSON or form-data).
     */
    protected function body(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw  = (string) file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }

        return $_POST;
    }

    protected function intParam(array $params, string $key, int $default = 0): int
    {
        return isset($params[$key]) ? (int) $params[$key] : $default;
    }
}
