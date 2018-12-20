<?php
declare(strict_types=1);

return [
    'view' => [
        'type' => 'twig',
        'templates_path' =>  './templates',
        'template_file_extension' => 'twig',
        'twig' => [
            'debug' => true, // disable on production
            'strict_variables' => true, // disable on production
            'autoescape' => 'html',
            'cache' => false, // change to a valid file path to enable caching  on production
            'auto_reload' => true, // disable on production
            'optimizations' => 0 // change to -1 on production
        ],
    ]
];
