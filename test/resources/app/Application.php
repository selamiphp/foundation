<?php
declare(strict_types=1);


namespace MyApp;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Psr\Http\Message\ServerRequestInterface;

abstract class Application
{

    /**
     * @var array
     */
    protected $uriParameters;
    /**
     * @var SymfonySession
     */
    protected $session;
    /**
     * @var ServerRequestInterface
     */
    protected $request;
}
