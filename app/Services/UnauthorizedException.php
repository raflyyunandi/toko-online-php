<?php

declare(strict_types=1);

namespace App\Services;

final class UnauthorizedException extends \RuntimeException
{
    /**
     * Exception untuk kasus akses tidak valid (misal admin key salah).
     */
    public function __construct(string $message = 'Tidak memiliki akses.')
    {
        parent::__construct($message);
    }
}
