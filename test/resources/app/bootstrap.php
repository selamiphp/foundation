<?php
declare(strict_types=1);

chdir(__DIR__);
require '../../../vendor/autoload.php';

$container = include './config/container.php';

use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\RequestHandlerRunner;

use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Stratigility\Middleware\ErrorResponseGenerator;

$errorResponseGenerator = function (Throwable $e) {
    $generator = new ErrorResponseGenerator();
    return $generator($e, new ServerRequest(), new Response());
};

$serverRequestFactory = [ServerRequestFactory::class, 'fromGlobals'];

$stack = new EmitterStack();
$stack->push(new SapiEmitter());

$myApp = Selami\Application::createWithContainer($container, 'selami-example-app');

$runner = new RequestHandlerRunner(
    $myApp,
    $stack,
    $serverRequestFactory,
    $errorResponseGenerator
);
$runner->run();
