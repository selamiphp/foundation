<?php
declare(strict_types=1);

namespace Selami;

use Psr\Container\ContainerInterface;
use Selami\Stdlib\Resolver;
use ReflectionClass;

class FrontController
{
    private $container;
    private $controller;
    private $controllerClass;
    private $uriParameters;

    public function __construct(
        ContainerInterface $container,
        string $controller,
        ?array $uriParameters = []
    ) {
        $this->container = $container;
        $this->controller = $controller;
        $this->uriParameters = $uriParameters;
    }

    public function getUriParameters() : array
    {
        return $this->uriParameters;
    }

    public function getControllerResponse() : ControllerResponse
    {
        $this->controllerClass = $this->controller;
        $controllerConstructorArguments = Resolver::getParameterHints($this->controllerClass, '__construct');
        $arguments = [];
        foreach ($controllerConstructorArguments as $argumentName => $argumentType) {
            $arguments[] = $this->getArgument($argumentName, $argumentType);
        }
        $controllerClass = new ReflectionClass($this->controllerClass);

        /**
         * @var $controllerObject \Selami\Interfaces\ApplicationController
         */
        $controllerObject = $controllerClass->newInstanceArgs($arguments);
        return $controllerObject();
    }

    private function getArgument(string $argumentName, string $argumentType)
    {
        if ($argumentType === Resolver::ARRAY && $argumentName === 'uriParameters') {
            return $this->getUriParameters();
        }
        return  $this->container->has($argumentType) ? $this->container->get($argumentType) :
            $this->container->get($argumentName);
    }
}
