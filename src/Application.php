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
     * @var ApplicationResponse
     */
    private $response;

    /**
     * @var ServerRequestInterface
     */
    private $request;
    private $requestedMethod;
    private $requestedPath;
    private $route;
    private $environment;

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
        $this->environment = $this->config->get('app')->get('environment', 'dev');
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
        return $this->response->returnResponse();
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    : ResponseInterface
    {
        $this->request = $request;
        $this->run();
        return $this->response->returnResponse();
    }

    private function run() : void
    {
        $this->requestedMethod = $this->request->getMethod();
        $this->requestedPath = $this->request->getUri()->getPath();
        $route = $this->getRoute();
        $statusCode = $route->getStatusCode();
        switch ($statusCode) {
            case 405:
                $this->notFound(405, 'Method Not Allowed');
                break;
            case 200:
                $this->runController(
                    $route->getController(),
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
        $errorHandlerClass = $this->config->get('app')->get('http-error-handler');
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
        $appConfig = $this->config->get('app');
        $this->router = $this->router
            ->withSubFolder($appConfig->get('app_sub_folder', ''));
        if ($this->environment  === 'prod'
            || $appConfig->get('router_cache_enabled', false) === true
        ) {
            $cacheFile = $appConfig->get('cache_dir').'/'.$this->id.'.fastroute.cache';
            $this->router = $this->router
                ->withCacheFile($cacheFile);
        }
        $this->addRoutes($this->config->get('routes'));
        return $this->route ?? $this->router->getRoute();
    }

    private function addRoutes($routes) : void
    {
        foreach ($routes as $route) {
            if ($this->requestedMethod === $route[0] && $this->requestedPath === $route[1]) {
                $this->route = new Route($route[0], $route[1], 200,  $route[3], $route[2], []);
                break;
            }
            $this->router->add($route[0], $route[1], $route[2], $route[3], $route[4] ?? '');
        }
    }

    private function runController($controllerClass, array $args) : void
    {
        $controller = new FrontController($this->container, $controllerClass, $args);
        $this->response = new ApplicationResponse(
            $controllerClass,
            $controller->getControllerResponse(),
            $this->config,
            $this->container->get(ViewInterface::class)
        );
    }
}
