<?php
declare(strict_types=1);

/**
 * Selami Application
 *
 * @link    https://github.com/selamiphp/core
 * @license https://github.com/selamiphp/core/blob/master/LICENSE (MIT License)
 */


namespace Selami\Core;

use Selami\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
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

    private $container;
    private $config;
    /**
     * ServerRequest
     *
     * @var ServerRequestInterface
     */
    private $request;
    private $route;
    private $session;
    private $response;

    public function __construct(
        ZendConfig $config,
        ServerRequestInterface $request,
        Router $router,
        SymfonySession $session,
        ContainerInterface $container
    ) {
    
        $this->request = $request;
        $this->config = $config;
        $this->route = $router->getRoute();
        $this->session = $session;
        $this->container  = $container;
    }

    public static function selamiApplicationFactory(ContainerInterface $container) : App
    {
        return new App(
            $container->get(ZendConfig::class),
            $container->get(ServerRequestInterface::class),
            $container->get(Router::class),
            $container->get(SymfonySession::class),
            $container
        );
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ) : ResponseInterface {
        $this->request = $request;
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
        $this->startSession();
        $this->runDispatcher($this->route['route']);
    }

    private function startSession() :void
    {
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.name', 'SELAMISESSID');
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
    }

    private function runDispatcher(array $route) : void
    {
        $this->response = new Result($this->container, $this->session);
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

    private function runRoute(string $controller, string $returnType = 'html', ?array $args) : void
    {
        if (!class_exists($controller)) {
            $message = "Controller has not class name as {$controller}";
            throw new \BadMethodCallException($message);
        }
        $controllerInstance = new $controller($this->container, $args);
        if (method_exists($controllerInstance, 'applicationLoad')) {
            $controllerInstance->applicationLoad();
        }
        if (method_exists($controllerInstance, 'controllerLoad')) {
            $controllerInstance->controllerLoad();
        }
        $functionOutput = $controllerInstance();

        $returnFunction = 'return' . ucfirst($returnType);
        $this->response->$returnFunction($functionOutput, $controller);
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
