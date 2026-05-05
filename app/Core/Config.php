<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, mixed> */
    private array $items = [];

    /** @var string */
    private string $basePath;

    /**
     * Inisialisasi loader konfigurasi dari folder config/.
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Mengambil nilai konfigurasi berdasarkan key bertingkat (contoh: database.sqlite.path).
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        if ($file === null || $file === '') {
            return $default;
        }

        if (!array_key_exists($file, $this->items)) {
            $path = rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $file . '.php';
            $this->items[$file] = is_file($path) ? require $path : [];
        }

        $value = $this->items[$file];
        foreach ($parts as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
