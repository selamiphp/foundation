<?php
declare(strict_types=1);

namespace Selami\Http;

use Psr\Http\Message\ResponseInterface as PsrResponse;

class Psr7Response
{
    public function __invoke(PsrResponse $response, $responseData)
    {
        $new = $this->psr7ResponseHeaders($response, $responseData);
        $new = $new->withStatus($responseData['statusCode']);
        $new = $this->psr7ResponseBody($new, $responseData);
        return $new;
    }

    private function psr7ResponseHeaders(PsrResponse $response, array $responseData)
    {
        $new = $response;
        foreach ($responseData['headers'] as $header => $value) {
            $new = $new->withHeader($header, $value);
        }
        return $new;
    }

    private function psr7ResponseBody(PsrResponse $response, array $responseData)
    {
        if ($responseData['contentType'] === 'redirect') {
            return $response->withHeader('Location', $responseData['redirect']);
        }
        if ($responseData['contentType'] === 'json') {
            $response->getBody()->write(json_encode($responseData['data']));
            return $response;
        }
        $response->getBody()->write($responseData['body']);
        return $response;
    }
}
