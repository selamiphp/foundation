<?php
declare(strict_types=1);

namespace Selami\Interfaces;

use Psr\Container\ContainerInterface;

interface Application
{
    public function __invoke(): array;
}
