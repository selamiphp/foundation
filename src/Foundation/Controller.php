<?php
declare(strict_types=1);

namespace Selami\Foundation;

use Psr\Container\ContainerInterface;
use Selami\Stdlib\Resolver;
use ReflectionClass;
use BadMethodCallException;
use Selami\Router;

class Controller
{
    const SIMPLE_RESPONSE = 'simpleFactoryResponse';
    const AUTOWIRED_RESPONSE = 'autowiredResponse';

    private $container;
    private $controller;
    private $controllerClass;
    private $returnType;
    private $args;
    private $actionOutput;

    /**
     * Controller constructor.
     * @param ContainerInterface $container
     * @param $controller
     * @param int $returnType
     * @param array|null $args
     */
    public function __construct(
        ContainerInterface $container,
        string $controller,
        int $returnType = Response::HTML,
        ?array $args = null
    ) {
    
        $this->container = $container;
        $this->controller = $controller;
        $this->returnType = $returnType;
        $this->args = $args;

        $this->autowiredResponse();
    }

    public function getControllerClass() : string
    {
        return $this->controllerClass;
    }

    public function getActionOutput() : array
    {
        return $this->actionOutput;
    }

    public function getReturnType() : int
    {
        return $this->returnType;
    }

    private function autowiredResponse() : void
    {
        $this->controllerClass = $this->controller;
        if (!class_exists($this->controllerClass)) {
            $message = "Controller has not class name as {$this->controllerClass}";
            throw new BadMethodCallException($message);
        }
        $controllerConstructorArguments = Resolver::getParameterHints($this->controllerClass, '__construct');
        $arguments = [];
        foreach ($controllerConstructorArguments as $argumentName => $argumentType) {
            $arguments[] = $this->getArgument($argumentName, $argumentType);
        }
        $reflectionClass = new ReflectionClass($this->controllerClass);
        $controller = $reflectionClass->newInstanceArgs($arguments);
        $this->actionOutput = $controller->__invoke();
        if (isset($this->actionOutput['meta']['type'])
            && $this->actionOutput['meta']['type'] === Dispatcher::REDIRECT
        ) {
            $this->returnType = Router::REDIRECT;
        }
    }

    private function getArgument(string $argumentName, string $argumentType)
    {
        if ($argumentType === Resolver::ARRAY) {
            return $this->container->has($argumentName) ?
                $this->container->get($argumentName) :  $this->{$argumentName};
        }
        return $this->container->get($argumentType);
    }
}
