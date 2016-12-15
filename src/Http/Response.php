<?php

namespace Selami\Http;

class Response
{
    protected $response     = null;
    protected $outputType   = 'html';
    protected $headers      = [];
    protected $statusCode   = 200;
    protected $body         = null;
    protected $redirectUri  = null;
    protected $version      = '1.0';
    protected static $validOutputTypes = ['html', 'json', 'text', 'redirect'];
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

    public function setHeaders($headers = null)
    {
        $this->headers['X-Powered-By']      = 'Selamy';
        $this->headers['X-Frame-Options']   = 'DENY';
        $this->headers['X-Frame-Options']   = 'SAMEORIGIN';
        $this->headers['X-XSS-Protection']  = '1; mode=block';
        $this->headers['Strict-Transport-Security'] = 'max-age=31536000';
        if (is_array($headers)) {
            foreach ($headers as $header => $value) {
                $this->setHeader($header, $value);
            }
        }
    }

    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function setRedirect($redirectUri, $code = 302)
    {
        $this->statusCode = $code;

        $this->redirectUri = $redirectUri;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function setData($body)
    {
        $this->body = json_encode($body);
    }

    public function setOutputType($type)
    {
        if (in_array($type, self::$validOutputTypes)) {
            $this->outputType = $type;
        }
    }

    public function getOutputType()
    {
        return $this->outputType;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
    public function getBody()
    {
        return $this->body;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function sendHeaders()
    {
        if ($this->outputType === 'json') {
            $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        }
        if ($this->outputType === 'text') {
            $this->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        }
        if ($this->outputType === 'html') {
            $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        }
        if (!headers_sent()) {
            if ($this->outputType === 'redirect' && $this->redirectUri !== null) {
                    header('location:' . $this->redirectUri);
                    return;
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

    public function sendContent()
    {
        echo $this->body;
    }

    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
    }
}
