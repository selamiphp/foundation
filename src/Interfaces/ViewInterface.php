<?php

namespace Selami\Interfaces;

interface ViewInterface
{
    public function addGlobal(string $name, $value);

    public function render(string $templateFile, array $parameters = null);
}
