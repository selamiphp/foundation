<?php

namespace Selami\Interfaces;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;

interface ServerRequestInterface extends PsrServerRequestInterface
{
    public function getParam(string $key, $default = null);

    public function getParams();
}
