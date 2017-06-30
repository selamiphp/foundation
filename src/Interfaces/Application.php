<?php
declare(strict_types=1);

namespace Selami\Interfaces;

use Psr\Container\ContainerInterface;

interface Application
{
    public function respond(): array;
    public static function factory(ContainerInterface $container, ?array $args) : Application;
}
