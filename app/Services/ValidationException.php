<?php

declare(strict_types=1);

namespace App\Services;

final class ValidationException extends \RuntimeException
{
    /** @var array<string, string> */
    public array $errors;

    /** @param array<string, string> $errors */
    /**
     * Exception untuk menandai request/payload tidak valid beserta detail error per field.
     *
     * @param array<string, string> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validasi gagal.');
    }
}
