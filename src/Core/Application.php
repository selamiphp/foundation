<?php

/**
 * Selami Application
 *
 * @link      https://github.com/selamiphp/core
 * @license   https://github.com/selamiphp/core/blob/master/LICENSE (MIT License)
 */

declare(strict_types = 1);

namespace Selami\Core;

use Selami as s;
use Selami\Router;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Selami\Http\Psr7Response;

class Application
{

    /**
     * ServerRequest
     *
     * @var ServerRequestInterface
     */
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
    private $request;
    private $route;
    private $controller;
    private $session;
    private $response;

    public function __construct(array $config, ServerRequestInterface $request, Router $router, SymfonySession $session)
    {
        $this->request = $request;
        $this->config = array_merge($this->config, $config);
        $this->route = $router->getRoute();
        $this->session = $session;
    }

    public static function selamiApplicationFactory(ContainerInterface $container)
    {
        return new Application(
            $container->get('config'),
            $container->get('request'),
            $container->get('router'),
            $container->get('session')
        );
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $this->request = $request;
        $this->run();
        $psr7Response  = new Psr7Response;
        $response = $psr7Response($response, $this->response);
        if ($next !== null) {
            $response  = $next($request, $response);
        }
        return $response;
    }

    private function run()
    {
        $this->startSession();
        $this->runDispatcher($this->route['route']);
    }

    private function startSession()
    {
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.name', 'SELAMISESSID');
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
    }

    private function runDispatcher(array $route)
    {
        $this->response = new Result($this->container, $this->session);

        switch ($route['status']) {
            case 405:
                $this->response->notFound(405, $this->config['default_return_type'], 'Method Not Allowed');
                break;
            case 200:
                $this->runRoute($route['controller'], $route['returnType'], $route['args']);
                break;
            case 404:
            default:
                $this->response->notFound(404, $this->config['default_return_type'], 'Not Found');
                break;
        }
    }

    private function runRoute(string $controller, string $returnType = 'html', array $args = [])
    {
        $this->controller = $controller;
        if (!class_exists($controller)) {
            $message = "Controller has not class name as {$controller}";
            throw new \BadMethodCallException($message);
        }
        $controllerInstance = new $controller($this->container, $args);
        $functionOutput = $controllerInstance->invoke();
        $returnFunction = 'return' . ucfirst($returnType);
        $this->response->$returnFunction($functionOutput, $this->controller);
    }

    public function getResponse()
    {
        $this->run();
        return $this->response->getResponse();
    }

    public function sendResponse()
    {
        $this->run();
        return $this->response->sendResponse();
    }
}
