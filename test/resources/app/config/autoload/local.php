<?php
declare(strict_types=1);

return [
    'app' => [
        'environment' => getenv('SELAMI_ENVIRONMENT'),
        'http-error-handler' => MyApp\ErrorHandler::class,
        'cache_dir' => './cache',
        'config_cache_enabled' => false, // enable on production
        'router_cache_enabled' => false
    ],
];
