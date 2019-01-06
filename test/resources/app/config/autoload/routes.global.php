<?php
declare(strict_types=1);

use Selami\Router\Router;

return [
    'routes' => [
        'selami-app' => [
            [Router::GET, '/', MyApp\Contents\Main::class, Router::HTML, 'home'],
            [Router::GET, '/category/{category}', MyApp\Contents\Category::class, Router::HTML, 'category'],
            [Router::GET, '/{year}/{month}/{slug}', MyApp\Contents\Post::class, Router::JSON, 'post'],
            [Router::GET, '/download', MyApp\Contents\Download::class, Router::DOWNLOAD],
            [Router::GET, '/text', MyApp\Contents\Text::class, Router::TEXT],
            [Router::GET, '/custom', MyApp\Contents\Custom::class, Router::CUSTOM],
            [Router::GET, '/redirect', MyApp\Contents\Redirect::class, Router::REDIRECT],
            [Router::GET, '/redirected', MyApp\Contents\Redirected::class, Router::HTML],
            [Router::POST, '/405', MyApp\Contents\NotFound::class, Router::JSON]
        ]
    ]
];
