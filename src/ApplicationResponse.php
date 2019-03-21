<?php
declare(strict_types=1);

namespace Selami;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Config\Config;
use Selami\View\ViewInterface;
use Selami\Router\Router;
use Selami\Stdlib\CaseConverter;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\XmlResponse;
use Zend\Diactoros\Stream;

class ApplicationResponse
{
    private $controllerClass;
    private $controllerResponse;
    private $config;
    private $view;
    private $headers;

    public function __construct(
        ServerRequestInterface $request,
        string $controllerClass,
        ControllerResponse $controllerResponse,
        Config $config,
        ViewInterface $view
    ) {
        $this->controllerClass = $controllerClass;
        $this->controllerResponse = $controllerResponse;
        $this->config = $config;
        $this->headers = isset( $config->get('app')['default_headers']) ?
            $config->get('app')->get('default_headers')->toArray() : [];
        $this->view = $view;
        $this->view->addGlobal('Request', $request);
        $this->view->addGlobal(
            'QueryParameters',
            array_merge($request->getQueryParams(), $request->getParsedBody())
        );
    }

    public function getResponseHeaders() : array
    {
        return array_merge($this->headers, $this->controllerResponse->getHeaders());
    }

    public function returnResponse() : ResponseInterface
    {
        switch ($this->controllerResponse->getReturnType()) {
            case Router::HTML:
                return new HtmlResponse(
                    $this->renderResponse(),
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders()
                );
                break;
            case Router::JSON:
                return new JsonResponse(
                    $this->controllerResponse->getData(),
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders(),
                    JsonResponse::DEFAULT_JSON_FLAGS | JSON_PARTIAL_OUTPUT_ON_ERROR
                );
                break;
            case Router::TEXT:
                return new TextResponse(
                    $this->renderResponse(),
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders()
                );
                break;
            case Router::XML:
                return new XmlResponse(
                    $this->renderResponse(),
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders()
                );
                break;
            case Router::DOWNLOAD:
                $metaData = $this->controllerResponse->getMetaData();
                /**
                 * @var $stream Stream
                 */
                $stream = $metaData['stream'];
                return new Response(
                    $stream,
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders()
                );
                break;
            case Router::REDIRECT:
                return new RedirectResponse(
                    $this->controllerResponse->getMetaData()['uri'],
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders()
                );
                break;
            case Router::CUSTOM:
                return new HtmlResponse(
                    $this->renderResponse(),
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders()
                );
                break;
            case Router::EMPTY:
                return new EmptyResponse(
                    $this->controllerResponse->getStatusCode(),
                    $this->getResponseHeaders()
                );
                break;
        }
    }

    private function renderResponse() : string
    {
        $paths = explode("\\", $this->controllerClass);
        $templateFile = array_pop($paths);
        $templateFolder = array_pop($paths);
        $template = CaseConverter::toSnakeCase($templateFolder)
            . '/' . CaseConverter::toSnakeCase($templateFile);
        $layout = $this->controllerResponse->getMetaData()['layout'] ?? $template;
        $templatePath = $layout. '.' . $this->config->view->get('template_file_extension');

        $this->checkTemplateFile($templatePath, 'Method\'s', $this->controllerClass);
        return $this->view->render($templatePath, $this->controllerResponse->getData());
    }

    private function checkTemplateFile($template, $type, $controller) : void
    {
        if (!file_exists($this->config->view->get('templates_path') .'/'. $template)) {
            $message  = sprintf(
                '%s  template file not found! %s  needs a main template file at: %s',
                $type,
                $controller,
                $this->config['app_dir'] .'/'. $template
            );
            throw new \DomainException($message);
        }
    }

    public function notFound(int $status, int $returnType, string $message) : ResponseInterface
    {
        if ($returnType === Router::JSON) {
            return new JsonResponse(['status' => $status, 'message' => $message], $status);
        }
        $notFoundTemplate = '_layouts/404.twig';
        $content = $this->view->render(
            $notFoundTemplate,
            ['message' => $message, 'status' => $status]
        );
        return new HtmlResponse($content, $status);
    }
}
