<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'toko-online-app'),
    'env' => Env::get('APP_ENV', 'local'),
];

