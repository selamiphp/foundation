<?php
declare(strict_types=1);
namespace Selami\Core;

use Selami as s;
use Psr\Container\ContainerInterface;
use Selami\View\ViewInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class Result
{
    private $config     = [];
    private $container;
    private $view;
    private $session;
    private $result   = [
        'statusCode'    => 200,
        'headers'       => [],
        'cookies'       => null,
        'body'          => '',
        'data'          => null,
        'contentType'   => 'html',
        'redirect'      => null
    ];

    public function __construct(ContainerInterface $container, Session $session)
    {
        $this->container = $container;
        $this->config = $container->get('config');
        $this->session =$session;
    }

    private function checkTemplateFile($template, $type, $controller) : void
    {
        if (!file_exists($this->config['templates_dir'] .'/'. $template)) {
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
        $this->result['contentType'] = 'redirect';
        if (isset($functionOutput['redirect'])) {
            $this->result['contentType']   = 'redirect';
            $this->result['status']       = 301;
            $this->result['redirect']     = $functionOutput['redirect'];
        }
    }

    public function returnJson(array $functionOutput, string $controller) : void
    {
        $this->result['contentType'] = 'json';
        if (isset($functionOutput['redirect'])) {
            $this->result['contentType']   = 'redirect';
            $this->result['status']       = 301;
            $this->result['redirect']     = $functionOutput['redirect'];
        }
        if (!is_array($functionOutput)) {
            $functionOutput = ['status' => 500, 'error' => 'Internal Server Error'];
        } elseif (!isset($functionOutput['status'])) {
            $functionOutput['status'] = 200;
        }
        $status = (int) $functionOutput['status'];
        $this->result['statusCode'] = $status;
        $this->result['data'] = $functionOutput;
    }

    public function returnHtml(array $functionOutput, string $controller) : void
    {
        $this->useView($this->container->get('view'));
        $paths = explode("\\", $controller);
        $templateFile = array_pop($paths);
        $templateFolder = array_pop($paths);
        $template = strtolower($templateFolder) . '/' . strtolower($templateFile) . '.twig';

        if (isset($functionOutput['redirect'])) {
            $this->result['contentType']   = 'redirect';
            $this->result['status']       = 301;
            $this->result['redirect']     = $functionOutput['redirect'];
        }
        $this->view->addGlobal('defined', get_defined_constants(true)['user'] ?? []);
        $this->view->addGlobal('session', $this->session->all());
        $this->checkTemplateFile($template, 'Method\'s', $controller);
        $functionOutput['data'] = $functionOutput['data'] ?? [];
        $functionOutput['app_content'] = $this->view->render($template, $functionOutput['data']);
        $mainTemplateName = $functionOutput['app_main_template'] ?? 'default';
        $mainTemplate = '_' . strtolower($mainTemplateName) . '.twig';
        $this->checkTemplateFile($mainTemplate, 'Main', $controller);
        $this->result['body'] = $this->view->render($mainTemplate, $functionOutput);
    }


    public function returnText(array $functionOutput, string $controller) : void
    {
        $this->useView($this->container->get('view'));
        $paths = explode("\\", $controller);
        $templateFile = array_pop($paths);
        $templateFolder = array_pop($paths);
        $template = strtolower($templateFolder) . '/' . strtolower($templateFile) . '.twig';
        if (isset($functionOutput['redirect'])) {
            $this->result['contentType']   = 'redirect';
            $this->result['status']       = 301;
            $this->result['redirect']     = $functionOutput['redirect'];
        }
        $this->view->addGlobal('defined', get_defined_constants(true)['user'] ?? []);
        $this->view->addGlobal('session', $this->session->all());
        $this->checkTemplateFile($template, 'Method\'s', $controller);
        $functionOutput['data'] = $functionOutput['data'] ?? [];
        $functionOutput['app_content'] = $this->view->render($template, $functionOutput['data']);
        $mainTemplateName = $functionOutput['app_main_template'] ?? 'default';
        $mainTemplate = '_' . strtolower($mainTemplateName) . '.twig';
        $this->checkTemplateFile($mainTemplate, 'Main', $controller);
        $this->result['contentType'] = 'text';
        $this->result['body'] = $this->view->render($mainTemplate, $functionOutput);
    }

    public function notFound($status = 404, $returnType = 'html', $message = 'Not Found') : void
    {
        if ($returnType == 'json') {
            $this->result['body'] = ['status' => $status, 'message' => $message];
        } else {
            $this->useView($this->container->get('view'));
            $notFoundTemplate = '_404.twig';
            $this->result['contentType'] = $returnType;
            $this->result['body'] = $this->view->render(
                $notFoundTemplate,
                ['message' => $message, 'status' => $status]
            );
        }
        $this->result['statusCode']=$status;
    }

    private function useView(ViewInterface $view) : void
    {
        $this->view = $view;
    }

    private function setHeaders() : void
    {
        $this->result['headers']['X-Powered-By']      = 'r/selami';
        $this->result['headers']['X-Frame-Options']   = 'SAMEORIGIN';
        $this->result['headers']['X-XSS-Protection']  = '1; mode=block';
        $this->result['headers']['Strict-Transport-Security'] = 'max-age=31536000';
        if (array_key_exists('headers', $this->config) && is_array($this->config['headers'])) {
            foreach ($this->config['headers'] as $header => $value) {
                $this->result['headers'][$header] = $value;
            }
        }
    }

    public function getResponse()
    {
        $headers = $this->config['headers'] ?? null;
        $this->setHeaders($headers);
        return $this->result;
    }

    public function sendResponse() : void
    {
        $response = new s\Http\Response();
        $response->setHeaders($this->result['headers']);
        $response->setStatusCode($this->result['statusCode']);
        switch ($this->result['contentType']) {
            case 'redirect':
                $response->setOutputType('redirect');
                $response->setRedirect($this->result['redirect']);
                break;
            case 'json':
                $response->setOutputType('json');
                $response->setData($this->result['data']);
                break;
            case 'text':
                $response->setOutputType('text');
                $response->setBody($this->result['body']);
                break;
            case 'html':
            default:
                $response->setOutputType('html');
                $response->setBody($this->result['body']);
                break;
        }
        $response->send();
    }
}
