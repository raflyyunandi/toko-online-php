<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @var int */
    public $status;

    /** @var array<string, string> */
    public $headers;

    /** @var string */
    public $body;

    /** @param array<string, string> $headers */
    /**
     * Membuat objek Response dengan status, header, dan body.
     *
     * @param array<string, string> $headers
     */
    public function __construct(int $status, array $headers, string $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Membuat response JSON dengan status code tertentu.
     */
    public static function json(array $payload, int $status = 200, array $headers = []): self
    {
        $headers = array_merge([
            'Content-Type' => 'application/json; charset=utf-8',
        ], $headers);

        return new self($status, $headers, (string) json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Membuat response error standar (format JSON) dengan code dan message.
     */
    public static function error(string $message, int $status, string $code = 'error', array $details = []): self
    {
        return self::json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
        ], $status);
    }

    /**
     * Mengirim response ke client (HTTP status, header, dan body).
     */
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}

