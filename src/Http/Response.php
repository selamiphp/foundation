<?php
declare(strict_types=1);

namespace Selami\Http;

use Selami\Router;

class Response
{

    protected $response;
    protected $outputType = Router::HTML;
    protected $headers = [];
    protected $statusCode = 200;
    protected $body;
    protected $redirectUri;
    protected $downloadFileName;
    protected $downloadFilePath;
    protected $version = '1.0';
    protected $customContentType;
    protected static $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    public function setHeaders(array $headers = []) : void
    {
        $this->headers['X-Powered-By']      = 'Selami';
        $this->headers['X-Frame-Options']   = 'SAMEORIGIN';
        $this->headers['X-XSS-Protection']  = '1; mode=block';
        $this->headers['Strict-Transport-Security'] = 'max-age=31536000';
        foreach ($headers as $header => $value) {
            $this->setHeader($header, $value);
        }
    }

    public function setHeader(string $header, string $value) : void
    {
        $this->headers[$header] = $value;
    }

    public function setStatusCode(int $statusCode) : void
    {
        $this->statusCode = $statusCode;
    }

    public function setRedirect(string $redirectUri, int $code = 302) : void
    {
        $this->statusCode = $code;

        $this->redirectUri = $redirectUri;
    }

    public function setBody(string $body) : void
    {
        $this->body = $body;
    }

    public function setDownloadFilePath(string $filePath) : void
    {
        $this->downloadFilePath = $filePath;
    }

    public function setDownloadFileName(string $fileName) : void
    {
        $this->downloadFileName = $fileName;
    }


    public function getDownloadFilePath() : ?string
    {
        return $this->downloadFilePath;
    }

    public function getDownloadFileName() : ?string
    {
        return $this->downloadFileName;
    }


    public function setCustomContentType(string $contentType) : void
    {
        $this->customContentType = $contentType;
    }


    public function getCustomContentType() : ?string
    {
        return $this->customContentType;
    }

    public function setData(array $body) : void
    {
        $this->body = json_encode($body);
    }

    public function setOutputType(int $type) : void
    {
        if ($type >=1 && $type<=7) {
            $this->outputType = $type;
        }
    }

    public function getOutputType() : int
    {
        return $this->outputType;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }
    public function getBody() : string
    {
        return $this->body;
    }

    public function getRedirectUri() : string
    {
        return $this->redirectUri;
    }

    public function sendHeaders() : void
    {
        if ($this->outputType === Router::JSON) {
            $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        } elseif ($this->outputType === Router::TEXT) {
            $this->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        } elseif ($this->outputType === Router::XML) {
            $this->setHeader('Content-Type', 'application/xml; charset=UTF-8');
        } elseif ($this->outputType === Router::HTML) {
            $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } elseif ($this->outputType === Router::CUSTOM) {
            $this->setHeader('Content-Type', $this->customContentType ?? 'text/plain');
        } elseif ($this->outputType === Router::DOWNLOAD) {
            $this->setHeader('Content-Type', 'application/octet-stream');
            if ($this->downloadFileName !== null) {
                $this->setHeader(
                    'Content-Disposition',
                    'attachment; filename="'.$this->downloadFileName.'"'
                );
            }
        }



        if (!headers_sent()) {
            if ($this->outputType === Router::REDIRECT && $this->redirectUri !== null) {
                    header('location:' . $this->redirectUri);
                    exit;
            }
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, false, $this->statusCode);
            }
            header(
                sprintf(
                    'HTTP/%s %s %s',
                    $this->version,
                    $this->statusCode,
                    self::$statusTexts[$this->statusCode]
                ),
                true,
                $this->statusCode
            );
        }
    }

    public function sendContent() : void
    {
        echo $this->getContent();
    }

    private function getContent()
    {
        if ($this->downloadFilePath !== null && file_exists($this->downloadFilePath)) {
            return file_get_contents($this->downloadFilePath);
        }
        return $this->body;
    }

    public function send() : void
    {
        $this->sendHeaders();
        $this->sendContent();
    }
}
