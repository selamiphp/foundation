<?php
declare(strict_types=1);

namespace Selami;

use Psr\Container\ContainerInterface;
use Selami\Router\Router;
use Selami\Stdlib\Resolver;
use ReflectionClass;

class ApplicationController
{
    private $container;
    private $controller;
    private $controllerClass;
    private $returnType;
    private $uriParameters;

    public function __construct(
        ContainerInterface $container,
        string $controller,
        int $returnType = Router::HTML,
        ?array $uriParameters = []
    ) {
        $this->container = $container;
        $this->controller = $controller;
        $this->returnType = $returnType;
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
         * @var $controllerObject \Selami\Interfaces\Controller
         */
        $controllerObject = $controllerClass->newInstanceArgs($arguments);
        return $controllerObject();
    }

    private function getArgument(string $argumentName, string $argumentType)
    {
        if ($argumentType === Resolver::ARRAY) {
            return $this->container->has($argumentName) ?
                $this->container->get($argumentName) :  $this->{'get'.ucfirst($argumentName)}();
        }
        return $this->container->get($argumentType);
    }
}
