<?php
declare(strict_types=1);

namespace Selami\Foundation;

use Selami as s;
use Zend\Config\Config as ZendConfig;
use Psr\Container\ContainerInterface;
use Selami\View\ViewInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class Response
{
    private $config;
    private $container;
    private $view;
    private $session;
    /**
     * @var int
     */
    private $statusCode = 200;
    /**
     * @var array
     */
    private $headers = [];
    /**
     * @var array
     */
    private $cookies = [];
    /**
     * @var string
     */
    private $body = '';

    /**
     * @var array
     */
    private $data = [];
    /**
     * @var string
     */
    private $contentType = 'html';

    /**
     * @var string
     */
    private $redirect;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ZendConfig::class);
    }

    private function checkTemplateFile($template, $type, $controller) : void
    {
        if (!file_exists($this->config->app->get('templates_dir', './templates') .'/'. $template)) {
            $message  = sprintf(
                '%s  template file not found! %s  needs a main template file at: %s',
                $type,
                $controller,
                $this->config['app_dir'] .'/'. $template
            );
            throw new \DomainException($message);
        }
    }

    public function returnRedirect(array $functionOutput, string $controller) : void
    {
        $this->contentType = 'redirect';
        if (isset($functionOutput['redirect'])) {
            $this->contentType = 'redirect';
            $this->statusCode = 301;
            $this->redirect = $functionOutput['redirect'];
        }
    }

    public function returnJson(array $functionOutput, string $controllerClass) : void
    {
        $this->contentType = 'json';
        if (isset($functionOutput['redirect'])) {
            $this->contentType = 'redirect';
            $this->statusCode = 301;
            $this->redirect = $functionOutput['redirect'];
        }
        if (!is_array($functionOutput)) {
            $functionOutput = ['status' => 500, 'error' => 'Internal Server Error'];
        } elseif (!isset($functionOutput['status'])) {
            $functionOutput['status'] = 200;
        }
        $status = (int) $functionOutput['status'];
        $this->statusCode = $status;
        $this->data = $functionOutput;
    }

    public function returnHtml(array $functionOutput, string $controllerClass) : void
    {
        $this->useSession();
        $this->useView($this->container->get(ViewInterface::class));
        $paths = explode("\\", $controllerClass);
        $templateFile = array_pop($paths);
        $templateFolder = array_pop($paths);
        $template = strtolower($templateFolder) . '/' . strtolower($templateFile) . '.twig';

        if (isset($functionOutput['redirect'])) {
            $this->contentType   = 'redirect';
            $this->statusCode       = 301;
            $this->redirect     = $functionOutput['redirect'];
        }
        $this->view->addGlobal('defined', get_defined_constants(true)['user'] ?? []);
        $this->view->addGlobal('session', $this->session->all());
        $this->checkTemplateFile($template, 'Method\'s', $controllerClass);
        $functionOutput['data'] = $functionOutput['data'] ?? [];
        $functionOutput['app_content'] = $this->view->render($template, $functionOutput['data']);
        $mainTemplateName = $functionOutput['app_main_template'] ?? 'default';
        $mainTemplate = '_' . strtolower($mainTemplateName) . '.twig';
        $this->checkTemplateFile($mainTemplate, 'Main', $controllerClass);
        $this->body = $this->view->render($mainTemplate, $functionOutput);
    }


    public function returnText(array $functionOutput, string $controllerClass) : void
    {
        $this->useSession();
        $this->useView($this->container->get(ViewInterface::class));
        $paths = explode("\\", $controllerClass);
        $templateFile = array_pop($paths);
        $templateFolder = array_pop($paths);
        $template = strtolower($templateFolder) . '/' . strtolower($templateFile) . '.twig';
        if (isset($functionOutput['redirect'])) {
            $this->contentType   = 'redirect';
            $this->statusCode       = 301;
            $this->redirect     = $functionOutput['redirect'];
        }
        $this->view->addGlobal('defined', get_defined_constants(true)['user'] ?? []);
        $this->view->addGlobal('session', $this->session->all());
        $this->checkTemplateFile($template, 'Method\'s', $controllerClass);
        $functionOutput['data'] = $functionOutput['data'] ?? [];
        $functionOutput['app_content'] = $this->view->render($template, $functionOutput['data']);
        $mainTemplateName = $functionOutput['layout'] ?? 'default';
        $mainTemplate = '_' . strtolower($mainTemplateName) . '.twig';
        $this->checkTemplateFile($mainTemplate, 'Main', $controllerClass);
        $this->contentType = 'text';
        $this->body = $this->view->render($mainTemplate, $functionOutput);
    }

    public function notFound($status = 404, $returnType = 'html', $message = 'Not Found') : void
    {
        if ($returnType == 'json') {
            $this->body = ['status' => $status, 'message' => $message];
        } else {
            $this->useView($this->container->get('view'));
            $notFoundTemplate = '_404.twig';
            $this->contentType = $returnType;
            $this->body = $this->view->render(
                $notFoundTemplate,
                ['message' => $message, 'status' => $status]
            );
        }
        $this->statusCode = $status;
    }

    private function useView(ViewInterface $view) : void
    {
        $this->view = $view;
    }

    private function useSession() : void
    {
        $this->session = $this->container->get(Session::class);
    }

    private function setHeaders() : void
    {
        $this->headers['X-Powered-By']      = 'r/selami';
        $this->headers['X-Frame-Options']   = 'SAMEORIGIN';
        $this->headers['X-XSS-Protection']  = '1; mode=block';
        $this->headers['Strict-Transport-Security'] = 'max-age=31536000';
        if (array_key_exists('headers', $this->config) && is_array($this->config['headers'])) {
            foreach ($this->config['headers'] as $header => $value) {
                $this->headers[$header] = $value;
            }
        }
    }

    public function getResponse() : array
    {
        $headers = $this->config['headers'] ?? null;
        $this->setHeaders($headers);
        return [
            'statusCode'    => $this->statusCode,
            'headers'       => $this->headers,
            'cookies'       => $this->cookies,
            'body'          => (string) $this->body,
            'data'          => $this->data,
            'contentType'   => $this->contentType,
            'redirect'      => $this->redirect
        ];
    }

    public function sendResponse() : void
    {
        $response = new s\Http\Response();
        $response->setHeaders($this->headers);
        $response->setStatusCode($this->statusCode);
        switch ($this->contentType) {
        case 'redirect':
            $response->setOutputType('redirect');
            $response->setRedirect($this->redirect);
            break;
        case 'json':
            $response->setOutputType('json');
            $response->setData($this->data);
            break;
        case 'text':
            $response->setOutputType('text');
            $response->setBody($this->body);
            break;
        case 'html':
        default:
            $response->setOutputType('html');
            $response->setBody($this->body);
            break;
        }
        $response->send();
    }
}
