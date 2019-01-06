<?php
declare(strict_types=1);

chdir(__DIR__);
require __DIR__ . '/../../../vendor/autoload.php';
$container = include __DIR__ . '/config/container.php';

use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;
$serverRequestFactory = [ServerRequestFactory::class, 'fromGlobals'];
$stack = new EmitterStack();
$stack->push(new SapiEmitter());
$myApp = Selami\Application::createWithContainer($container, 'selami-app');
$response = $myApp->handle($serverRequestFactory());
$stack->emit($response);