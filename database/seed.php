<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

if (is_file($basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require $basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($basePath): void {
        if (substr($class, 0, 4) !== 'App\\') {
            return;
        }
        $relative = str_replace('App\\', 'app\\', $class);
        $path = $basePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

\App\Core\Env::load($basePath);

$config = new \App\Core\Config($basePath);
$connection = new \App\Database\Connection($config, $basePath);
$pdo = $connection->pdo();
\App\Database\Schema::ensure($pdo);

$product = new \App\Models\Product($pdo);
$p = $product->create('Flash Sale Item', 10000, 10);

fwrite(STDOUT, "Seeder selesai. Product ID: {$p['id']}\n");
