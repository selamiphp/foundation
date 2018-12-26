<?php
declare(strict_types=1);

return [
    'app' => [
        'environment' => getenv('SELAMI_ENVIRONMENT'),
        'cache_dir' => './cache',
        'config_cache_enabled' => false, // enable on production
        'router_cache_enabled' => false
    ],
];
