<?php
declare(strict_types=1);

/**
 * Selami Application
 *
 * @link    https://github.com/selamiphp/core
 * @license https://github.com/selamiphp/core/blob/master/LICENSE (MIT License)
 */


namespace Selami\Foundation;

use Selami\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selami\Http\Psr7Response;
use Zend\Config\Config as ZendConfig;


class App
{

    /*
    private $config = [
        'base_dir'      => null,
        'app_dir'       => '/var/lib/www/app',
        'app_data_dir'  => '/tmp',
        'base_url'      => null,
        'app_namespace' => 'SelamiApp',
        'app_name'      => 'www',
        'default_return_type'   => 'html',
        'template_engine'       => 'Twig',
        'bypass_error_handlers' => true,
        'aliases'       => []
    ];
    */
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var ZendConfig
     */
    private $config;
    /**
     * @var array
     */
    private $route;

    /**
     * @var array
     */
    private $response;

    public function __construct(
        ZendConfig $config,
        Router $router,
        ContainerInterface $container
    ) {

        $this->config = $config;
        $this->route = $router->getRoute();
        $this->container  = $container;
    }

    public static function selamiApplicationFactory(ContainerInterface $container) : App
    {
        return new App(
            $container->get(ZendConfig::class),
            $container->get(Router::class),
            $container
        );
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ) : ResponseInterface {
        $this->run();
        $psr7Response  = new Psr7Response;
        $response = $psr7Response($response, $this->response);
        if ($next !== null) {
            $response  = $next($request, $response);
        }
        return $response;
    }

    private function run() : void
    {
        $this->runDispatcher($this->route['route']);
    }



    private function runDispatcher(array $route) : void
    {
        $this->response = new Response($this->container);
        $defaultReturnType = $this->config->app->get('default_return_type', 'html');
        switch ($route['status']) {
        case 405:
            $this->response->notFound(405, $defaultReturnType, 'Method Not Allowed');
            break;
        case 200:
            $this->runRoute($route['controller'], $route['returnType'], $route['args']);
            break;
        case 404:
        default:
            $this->response->notFound(404, $defaultReturnType, 'Not Found');
            break;
        }
    }

    private function runRoute(string $controllerClass, string $returnType = 'html', ?array $args) : void
    {
        if (!class_exists($controllerClass)) {
            $message = "Controller has not class name as {$controllerClass}";
            throw new \BadMethodCallException($message);
        }
        $controller = $controllerClass::factory($this->container, $args);
        $functionOutput = $controller->respond();
        $returnFunction = 'return' . ucfirst($returnType);
        $this->response->$returnFunction($functionOutput, $controllerClass);
    }

    public function getResponse() : array
    {
        $this->run();
        return $this->response->getResponse();
    }

    public function sendResponse() : void
    {
        $this->run();
        $this->response->sendResponse();
    }
}