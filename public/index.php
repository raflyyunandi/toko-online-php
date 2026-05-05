<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $uriPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $filePath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uriPath);

    if ($uriPath !== '/' && is_file($filePath)) {
        return false;
    }
}

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

$adminAuth = new \App\Services\AdminAuth();

$controllers = [
    'product' => new \App\Controllers\Api\ProductController(
        new \App\Models\Product($pdo),
        new \App\Models\OrderItem($pdo),
        $adminAuth
    ),
    'order' => new \App\Controllers\Api\OrderController(
        new \App\Services\OrderService($pdo),
        new \App\Models\Order($pdo),
        new \App\Models\OrderItem($pdo),
        $adminAuth,
    ),
    'home' => new \App\Controllers\Web\HomeController(),
];

$router = new \App\Core\Router();

require $basePath . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
require $basePath . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'api.php';

$request = \App\Core\Request::fromGlobals();
$response = $router->dispatch($request);
$response->send();
