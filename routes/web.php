<?php

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */
/** @var array<string, object> $controllers */

$router->add('GET', '/', [$controllers['home'], 'index']);

