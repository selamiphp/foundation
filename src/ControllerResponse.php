<?php
declare(strict_types=1);

namespace Selami;

use Selami\Router\Router;
use Zend\Diactoros\Stream;
use finfo;

final class ControllerResponse
{
    private $returnType;
    private $headers;
    private $data;
    private $statusCode;
    private $metaData;



    public function __construct(int $returnType, int $statusCode, array $headers, array $data, array $metaData)
    {
        $this->returnType = $returnType;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->data = $data;
        $this->metaData = $metaData;
    }

    public static function CUSTOM(int $statusCode, array $data, array $metaData, array $headers) : self
    {
        return new self(Router::CUSTOM, $statusCode, $headers, $data, $metaData);
    }
    public static function EMPTY(int $statusCode, array $data, array $metaData, array $headers) : self
    {
        return new self(Router::EMPTY, $statusCode, $headers, $data, $metaData);
    }

    public static function HTML(int $statusCode, array $data, ?array $metaData = []) : self
    {
        return new self(Router::HTML, $statusCode, [], $data, $metaData);
    }

    public static function JSON(int $statusCode, array $data, ?array $metaData = []) : self
    {
        return new self(Router::JSON, $statusCode, [], $data, $metaData);
    }

    public static function TEXT(int $statusCode, array $data, ?array $metaData = []) : self
    {
        return new self(Router::TEXT, $statusCode, [], $data, $metaData);
    }

    public static function XML(int $statusCode, array $xmlData, ?array $metaData = []) : self
    {
        return new self(Router::XML, $statusCode, [], $xmlData, $metaData);
    }

    public static function REDIRECT(int $statusCode, string $redirectUrl) : self
    {
        return new self(Router::REDIRECT, $statusCode, [], [], ['uri' => $redirectUrl]);
    }

    public static function DOWNLOAD(int $statusCode, string $filePath, ?string $fileName = null) : self
    {

        $stream = new Stream(realpath($filePath), 'r');
        $headers = [
            'Content-Type' => (new finfo(FILEINFO_MIME))->file($filePath),
            'Content-Disposition' => 'attachment; filename=' . $fileName ?? basename($filePath),
            'Content-Transfer-Encoding' => 'Binary',
            'Content-Description' => 'File Transfer',
            'Pragma' =>  'public',
            'Expires' => '0',
            'Cache-Control' =>  'must-revalidate',
            'Content-Length' =>  (string) $stream->getSize()
        ];
        return new self(Router::DOWNLOAD, $statusCode, $headers, [], ['stream' => $stream]);
    }

    public function getReturnType() : int
    {
        return $this->returnType;
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function getMetaData() : array
    {
        return $this->metaData;
    }
}
