<?php

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */
/** @var array<string, object> $controllers */

$router->add('GET', '/api/health', static function () {
    return \App\Core\Response::json(['data' => ['status' => 'ok']], 200);
});

$router->add('GET', '/api/products', [$controllers['product'], 'index']);
$router->add('GET', '/api/products/{id}', [$controllers['product'], 'show']);
$router->add('POST', '/api/products', [$controllers['product'], 'store']);
$router->add('PATCH', '/api/products/{id}/stock', [$controllers['product'], 'setStock']);
$router->add('GET', '/api/products/{id}/orders', [$controllers['product'], 'orders']);

$router->add('POST', '/api/orders', [$controllers['order'], 'store']);
$router->add('GET', '/api/orders', [$controllers['order'], 'index']);
$router->add('GET', '/api/orders/{id}', [$controllers['order'], 'show']);
$router->add('GET', '/api/customers', [$controllers['order'], 'customers']);
