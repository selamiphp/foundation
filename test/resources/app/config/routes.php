<?php
declare(strict_types=1);

use Selami\Router\Router;

$router = new Router(
    $config['default_return_type'] ?? Router::HTML,
    $request->getMethod(),
    $request->getUri()->getPath()
);

return $router;
