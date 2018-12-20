<?php
declare(strict_types=1);

namespace Selami\Interfaces;

use Selami\ControllerResponse;

interface Controller
{
    public function __invoke() : ControllerResponse;
}
