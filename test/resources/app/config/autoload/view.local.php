<?php
declare(strict_types=1);
$twigConfig = [
    'debug' => true, // disable on production
    'strict_variables' => true, // disable on production
    'autoescape' => 'html',
    'cache' => false, // change to a valid file path to enable caching  on production
    'auto_reload' => true, // disable on production
    'optimizations' => -1 // change to -1 on production
];

if(getenv('SELAMI_ENVIRONMENT') === 'prod') {
    $twigConfig = [
        'debug' => false, // disable on production
        'strict_variables' => false, // disable on production
        'autoescape' => 'html',
        'cache' => './cache/twig', // change to a valid file path to enable caching  on production
        'auto_reload' => false, // disable on production
        'optimizations' => -1 // change to -1 on production
    ];
}



return [
    'view' => [
        'type' => 'twig',
        'templates_path' =>  './templates',
        'template_file_extension' => 'twig',
        'twig' => $twigConfig
    ]
];
