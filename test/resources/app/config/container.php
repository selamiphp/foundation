<?php
declare(strict_types=1);

use Zend\ServiceManager\ServiceManager;
use Selami\Router\Router;
use Psr\Http\Message\ServerRequestInterface;
use Selami\View\ViewInterface;
use Twig\Environment as TwigEnvironment;
use Zend\Config\Config;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Selami\Stdlib\BaseUrlExtractor;

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$config = include __DIR__ . '/config.php';
$container = new ServiceManager($config['dependencies']);
if (PHP_SAPI !== 'cli') {
    $config['app']['base_url'] = BaseUrlExtractor::getBaseUrl($_SERVER);
}

$container->setService(Config::class, new Config($config));
$container->setService('http-error-handler', MyApp\ErrorHandler::class);
$container->setService('http_error_handler', 'Deneme');

$container->setFactory(SessionInterface::class, function () {
    $sessionStorage = new NativeSessionStorage(array(), new NativeFileSessionHandler());
    return new Session($sessionStorage);
});
$container->setService(ServerRequestInterface::class, $request);
$container->setService(Router::class, require __DIR__ . '/routes.php');

$container->setFactory(
    TwigEnvironment::class,
    function () use ($config) {
        $loader = new Twig\Loader\FilesystemLoader($config['view']['templates_path']);
        return new TwigEnvironment($loader, $config['view']['twig']);
    }
);


$container->setFactory(
    ViewInterface::class,
    function ($container) use ($config, $request) {

        $viewConfig = $config['view'];
        $viewConfig['runtime']['query_parameters'] =  $request->getQueryParams();
        $viewConfig['runtime']['base_url'] =  $config['app']['base_url'];
        $viewConfig['runtime']['config'] = $config;
        return Selami\View\Twig\Twig::viewFactory($container, $viewConfig);
    }
);
return $container;
