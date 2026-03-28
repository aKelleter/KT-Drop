<?php
declare(strict_types=1);

namespace App\Core;

final class ApiRouter
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Handle method override for PATCH/DELETE via X-HTTP-Method-Override header
        $override = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '');
        if ($method === 'POST' && in_array($override, ['PATCH', 'DELETE', 'PUT'], true)) {
            $method = $override;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['pattern']);

            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                $params  = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$class, $methodName] = $handler;
                    (new $class())->{$methodName}($params);
                } else {
                    $handler($params);
                }

                return;
            }
        }

        ApiResponse::error('Endpoint introuvable', 404);
    }
}
