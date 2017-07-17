<?php
declare(strict_types=1);

namespace Selami\Http;

use Selami;
use Zend\Diactoros\ServerRequest as DiactorosServerRequest;

final class ServerRequest extends DiactorosServerRequest
{
    public function getParam(string $key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $getParams = $this->getQueryParams();
        $return = $default;
        if (is_array($postParams) && array_key_exists($key, $postParams)) {
            $return = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $return = $postParams->$key;
        } elseif (isset($getParams[$key])) {
            $return = $getParams[$key];
        }
        return $return;
    }

    public function getParams() : array
    {
        $params = $this->getQueryParams();
        $postParams = $this->getParsedBody();
        if ($postParams) {
            $params = array_merge($params, (array)$postParams);
        }
        return $params;
    }
}
