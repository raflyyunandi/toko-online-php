<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'driver' => Env::get('DB_DRIVER', 'sqlite'),
    'sqlite' => [
        'path' => Env::get('DB_PATH', 'database/app.sqlite'),
    ],
];

