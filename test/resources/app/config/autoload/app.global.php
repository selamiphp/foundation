<?php
declare(strict_types=1);

return [
    'app' => [
        'app_namespace' => 'MyApp',
        'template_engine' => 'Twig',
        'templates_path' =>  './templates',
        'cache' => './cache/twig',
        'debug' => false,
        'auto_reload' => true,
        'base_url' => 'http://127.0.0.1:8080'
    ],
    'view' => [
        'type' => 'twig',
        'twig' => [
            'cache' => './cache/twig',
            'debug' => false,
        ]
    ]
];
