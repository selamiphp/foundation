<?php
declare(strict_types=1);

namespace Selami\Interfaces;

use Selami\ControllerResponse;

interface ApplicationController
{
    public function __invoke() : ControllerResponse;
}
