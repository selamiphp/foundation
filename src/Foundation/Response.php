<?php
declare(strict_types=1);

namespace Selami\Foundation;

use Selami;
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
    private $contentType = Selami\Router::HTML;

    /**
     * @var string
     */
    private $redirect;
    private $downloadFilePath;
    private $downloadFileName;
    private $customContentType;

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

    public function setResponse(int $returnType, array $actionOutput, string $controller) : void
    {
        switch ($returnType) {
            case Selami\Router::HTML:
                $this->setRenderedResponse(Selami\Router::HTML, $actionOutput, $controller);
                break;
            case Selami\Router::JSON:
                $this->setJsonResponse($actionOutput);
                break;
            case Selami\Router::TEXT:
                $this->setRenderedResponse(Selami\Router::TEXT, $actionOutput, $controller);
                break;
            case Selami\Router::XML:
                $this->setRenderedResponse(Selami\Router::XML, $actionOutput, $controller);
                break;
            case Selami\Router::DOWNLOAD:
                $this->setDownloadResponse($actionOutput);
                break;
            case Selami\Router::CUSTOM:
                $this->setRenderedResponse(Selami\Router::CUSTOM, $actionOutput, $controller);
                break;
            case Selami\Router::REDIRECT:
                $this->setRedirectResponse($actionOutput);
                break;
        }
    }

    public function setRedirectResponse(array $actionOutput) : void
    {
        $this->contentType = Selami\Router::REDIRECT;
        if (isset($actionOutput['meta']['type']) && $actionOutput['meta']['type'] === Dispatcher::REDIRECT) {
            $this->contentType = Selami\Router::REDIRECT;
            $this->statusCode = $actionOutput['status'] ?? 302;
            $this->redirect = $actionOutput['meta']['redirect_url'];
        }
    }
    
    public function setDownloadResponse(array $actionOutput) : void
    {
        $this->contentType = Selami\Router::DOWNLOAD;
        if (isset($actionOutput['meta']['type']) ?? $actionOutput['meta']['type'] === Dispatcher::DOWNLOAD) {
            $statusCode = $actionOutput['status'] ?? 200;
            $this->statusCode = (int) $statusCode;
            $this->downloadFilePath = $actionOutput['meta']['download_file_path'];
            $this->downloadFileName = $actionOutput['meta']['download_file_name'] ?? date('Ymdhis');
        }
    }
    public function setJsonResponse(array $actionOutput) : void
    {
        $this->contentType = Selami\Router::JSON;

        if (!is_array($actionOutput)) {
            $actionOutput = ['status' => 500, 'error' => 'Internal Server Error'];
        }
        if (!isset($actionOutput['status'])) {
            $actionOutput['status'] = 200;
        }
        $status = (int) $actionOutput['status'];
        $this->statusCode = $status;
        $this->data = $actionOutput;
    }

    private function setRenderedResponse(int $returnType, array $actionOutput, string $controllerClass) : void
    {
        $this->useSession();
        $this->useView($this->container->get(ViewInterface::class));
        $this->view->addGlobal('defined', get_defined_constants(true)['user'] ?? []);
        $this->view->addGlobal('session', $this->session->all());
        $this->renderResponse($returnType, $actionOutput, $controllerClass);
    }

    private function renderResponse(int $returnType, array $actionOutput, string $controllerClass) : void
    {

        $paths = explode("\\", $controllerClass);
        $templateFile = array_pop($paths);
        $templateFolder = array_pop($paths);
        $template = strtolower($templateFolder) . '/' . strtolower($templateFile) . '.twig';
        $this->checkTemplateFile($template, 'Method\'s', $controllerClass);
        $actionOutput['data'] = $actionOutput['data'] ?? [];
        $output = [
            'status' => $actionOutput['status'] ?? 200,
            'meta' => $actionOutput['meta'] ?? [],
            'app' => null
        ];
        $output['app']['_content'] = $this->view->render($template, $actionOutput['data']);
        $mainTemplateName = $actionOutput['meta']['layout'] ?? 'default';
        $mainTemplate = '_' . strtolower($mainTemplateName) . '.twig';
        $this->checkTemplateFile($mainTemplate, 'Layout', $controllerClass);
        $this->contentType = $returnType;
        if ($returnType === Selami\Router::CUSTOM) {
            $this->customContentType = $actionOutput['meta']['content_type'] ?? 'plain/text';
        }
        $this->body = $this->view->render($mainTemplate, $output);
    }


    public function notFound($status = 404, $returnType = Selami\Router::HTML, $message = 'Not Found') : void
    {
        if ($returnType === Selami\Router::JSON) {
            $this->body = ['status' => $status, 'message' => $message];
        } else {
            $this->useView($this->container->get(ViewInterface::class));
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
        $response = new Selami\Http\Response();
        $response->setHeaders($this->headers);
        $response->setStatusCode($this->statusCode);
        switch ($this->contentType) {
            case Selami\Router::REDIRECT:
                $response->setOutputType(Selami\Router::REDIRECT);
                $response->setRedirect($this->redirect);
                break;
            case Selami\Router::CUSTOM:
                $response->setOutputType(Selami\Router::CUSTOM);
                $response->setCustomContentType($this->customContentType);
                $response->setBody($this->body);
                break;
            case Selami\Router::DOWNLOAD:
                $response->setOutputType(Selami\Router::DOWNLOAD);
                $response->setDownloadFileName($this->downloadFileName);
                $response->setDownloadFilePath($this->downloadFilePath);
                break;
            case Selami\Router::JSON:
                $response->setOutputType(Selami\Router::JSON);
                $response->setData($this->data);
                break;
            case Selami\Router::TEXT:
                $response->setOutputType(Selami\Router::TEXT);
                $response->setBody($this->body);
                break;
            case Selami\Router::XML:
                $response->setOutputType(Selami\Router::XML);
                $response->setBody($this->body);
                break;
            case Selami\Router::HTML:
            default:
                $response->setOutputType(Selami\Router::HTML);
                $response->setBody($this->body);
                break;
        }
        $response->send();
    }
}
