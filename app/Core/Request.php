<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var string */
    public $method;

    /** @var string */
    public $path;

    /** @var array<string, mixed> */
    public $query;

    /** @var array<string, string> */
    public $headers;

    /** @var string */
    public $rawBody;

    /** @var array<string, mixed> */
    public $json;

    /** @var array<string, string> */
    public $params = [];

    /**
     * Membuat objek Request secara manual (dipakai oleh router/controller).
     *
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @param array<string, mixed> $json
     * @param array<string, string> $params
     */
    public function __construct(string $method, string $path, array $query, array $headers, string $rawBody, array $json, array $params = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->headers = $headers;
        $this->rawBody = $rawBody;
        $this->json = $json;
        $this->params = $params;
    }

    /**
     * Membuat objek Request dari variabel global PHP ($_SERVER, $_GET, php://input).
     */
    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $rawBody = (string) file_get_contents('php://input');
        $json = [];

        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $name = str_replace('_', '-', strtolower(substr($k, 5)));
                $headers[$name] = (string) $v;
            }
        }

        return new self($method, $path, $_GET ?? [], $headers, $rawBody, $json);
    }
}

