<?php
declare(strict_types=1);

namespace Selami\Http;

use Zend\Diactoros\ServerRequestFactory as DiactorosServerRequestFactory;

final class ServerRequestFactory extends DiactorosServerRequestFactory
{
    private static function marshalProtocolVersion(array $server)
    {
        if (! isset($server['SERVER_PROTOCOL'])) {
            return '1.1';
        }
        if (! preg_match('#^(HTTP/)?(?P<version>[1-9]\d*(?:\.\d)?)$#', $server['SERVER_PROTOCOL'], $matches)) {
            throw new \UnexpectedValueException(sprintf(
                'Unrecognized protocol version (%s)',
                $server['SERVER_PROTOCOL']
            ));
        }
        return $matches['version'];
    }

    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) {
        $server  = parent::normalizeServer($server);
        $files   = parent::normalizeFiles($files);
        $headers = parent::marshalHeaders($server);
        return new ServerRequest(
            $server,
            $files,
            parent::marshalUriFromServer($server, $headers),
            parent::get('REQUEST_METHOD', $server, 'GET'),
            'php://input',
            $headers,
            $cookies,
            $query,
            $body,
            self::marshalProtocolVersion($server)
        );
    }
}
