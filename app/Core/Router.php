<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:callable}> */
    private array $routes = [];

    /**
     * Menambahkan route baru dengan method HTTP, pola path, dan handler.
     */
    public function add(string $method, string $pattern, callable $handler): void
    {
        $method = strtoupper($method);
        $pattern = '/' . ltrim($pattern, '/');

        $segments = explode('/', trim($pattern, '/'));
        $regexSegments = [];
        foreach ($segments as $seg) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $seg, $m) === 1) {
                $regexSegments[] = '(?P<' . $m[1] . '>[^/]+)';
                continue;
            }
            $regexSegments[] = preg_quote($seg, '#');
        }

        $regex = '#^/' . implode('/', $regexSegments) . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    /**
     * Mencocokkan request ke route yang sesuai dan menjalankan handler-nya.
     */
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }
            $request->params = $params;

            try {
                $response = ($route['handler'])($request);
                if ($response instanceof Response) {
                    return $response;
                }

                return Response::json(['data' => $response], 200);
            } catch (\Throwable $e) {
                $details = [];
                $env = \App\Core\Env::get('APP_ENV', 'local');
                if ($env !== 'production') {
                    $details = [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ];
                }

                return Response::error('Terjadi kesalahan pada server.', 500, 'server_error', $details);
            }
        }

        return Response::error('Endpoint tidak ditemukan.', 404, 'not_found');
    }
}
