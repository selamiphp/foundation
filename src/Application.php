<?php
declare(strict_types=1);

namespace Selami;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selami\Router\Route;
use Selami\Router\Router;
use Selami\View\ViewInterface;
use Zend\Diactoros\Response;
use Zend\Config\Config;

class Application implements RequestHandlerInterface
{

    private $id;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Router
     */
    private $router;

    /**
     * @var array
     */
    private $response;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(
        string $id,
        ContainerInterface $container,
        Router $router,
        Config $config
    ) {
        $this->id = $id;
        $this->config = $config;
        $this->router = $router;
        $this->container  = $container;
    }

    public static function createWithContainer(ContainerInterface $container, ?string $id = 'selami-app') : self
    {
        return new self(
            $id,
            $container,
            $container->get(Router::class),
            $container->get(Config::class)
        );
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->request = $request;
        $this->run();
        return $this->response->returnResponse(new Response());
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    : ResponseInterface
    {
        $this->request = $request;
        $this->run();
        return $this->response->returnResponse($response);
    }

    private function run() : void
    {
        $route = $this->getRoute();
        $statusCode = $route->getStatusCode();
        switch ($statusCode) {
            case 405:
                $this->notFound(405, 'Method Not Allowed');
                break;
            case 200:
                $this->runRoute(
                    $route->getController(),
                    $route->getReturnType(),
                    $route->getUriParameters()
                );
                break;
            case 404:
            default:
                $this->notFound($statusCode, 'Not Found');
                break;
        }
    }

    private function notFound($status, $message) : void
    {
        $errorHandlerClass = $this->container->get('http-error-handler');
        $errorHandler = new $errorHandlerClass($status, $message);
        $this->response = new ApplicationResponse(
            $errorHandlerClass,
            $errorHandler(),
            $this->config,
            $this->container->get(ViewInterface::class)
        );
    }

    private function getRoute() : Route
    {
        $this->router = $this->router
            ->withDefaultReturnType($this->config->app->get('default_return_type', Router::HTML))
            ->withSubFolder($this->config->app->get('app_sub_folder', ''));
        $cacheFile = $this->config->app->get('router_cache_file-' . $this->id, null);
        if ((bool) $cacheFile) {
            $this->router = $this->router
                ->withCacheFile($cacheFile);
        }
        $this->addRoutes($this->config->routes);
        return $this->router->getRoute();
    }

    private function addRoutes($routes) : void
    {
        foreach ($routes as $route) {
            $this->router->add($route[0], $route[1], $route[2], $route[3], $route[4] ?? '');
        }
    }

    private function runRoute($controllerClass, int $returnType, array $args) : void
    {
        $controller = new ApplicationController($this->container, $controllerClass, $returnType, $args);
        $this->response = new ApplicationResponse(
            $controllerClass,
            $controller->getControllerResponse(),
            $this->config,
            $this->container->get(ViewInterface::class)
        );
    }
}
