<?php
declare(strict_types=1);


namespace MyApp\Contents;

use MyApp\Application;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zend\Config\Config;

abstract class ContentsController extends Application
{

    protected $config;

    public function __construct(
        Config $config,
        ServerRequestInterface $request,
        SessionInterface $session,
        array $uriParameters
    ) {
        $this->session = $session;
        $this->request = $request;
        $this->uriParameters = $uriParameters;
        $this->config = $config;
    }
}
