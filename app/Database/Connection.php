<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Config;
use PDO;

final class Connection
{
    private ?PDO $pdo = null;

    private Config $config;
    private string $basePath;

    /**
     * Inisialisasi koneksi database berdasarkan konfigurasi.
     */
    public function __construct(Config $config, string $basePath)
    {
        $this->config = $config;
        $this->basePath = $basePath;
    }

    /**
     * Mengembalikan instance PDO (dibuat sekali dan di-cache).
     */
    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $driver = (string) $this->config->get('database.driver', 'sqlite');
        if ($driver !== 'sqlite') {
            throw new \RuntimeException('Hanya driver sqlite yang disiapkan pada proyek ini.');
        }

        $relativePath = (string) $this->config->get('database.sqlite.path', 'database/app.sqlite');
        $dbPath = $this->resolvePath($relativePath);

        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        $this->pdo = $pdo;
        return $this->pdo;
    }

    /**
     * Mengubah path relatif DB menjadi path absolut berdasarkan basePath proyek.
     */
    private function resolvePath(string $relativeOrAbsolutePath): string
    {
        if ($relativeOrAbsolutePath === '') {
            return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'app.sqlite';
        }

        if (preg_match('/^[A-Za-z]:\\\\/', $relativeOrAbsolutePath) === 1 || substr($relativeOrAbsolutePath, 0, 1) === DIRECTORY_SEPARATOR) {
            return $relativeOrAbsolutePath;
        }

        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeOrAbsolutePath);
    }
}

