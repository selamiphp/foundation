<?php

namespace tests;

use Selami as s;
use Zend\Diactoros\Response as DiactorosResponse;
use PHPUnit\Framework\TestCase;

class TestPsr7Response extends TestCase
{
    private $response   = [
        'statusCode'    => 200,
        'headers'       => ['X-TEST' => '1'],
        'cookies'       => null,
        'body'          => '<html><body><p>Test</p></body></html>',
        'data'          => ['status' => 200, 'data' => ['health' => 'OK']],
        'contentType'   => 'html',
        'redirect'      => 'http://127.0.0.1:8080/redirect'
    ];

    /**
     * @test
     */
    public function shouldReturnPsr7Response()
    {
        $psr7Response  = new s\Http\Psr7Response;
        $response = $psr7Response(new DiactorosResponse(), $this->response);
        $this->assertInstanceOf(DiactorosResponse::class, $response);
        $this->response['contentType']='redirect';
        $psr7Response  = new s\Http\PSR7Response;
        $response = $psr7Response(new DiactorosResponse(), $this->response);
        $this->assertInstanceOf(DiactorosResponse::class, $response);
        $this->response['contentType']='json';
        $psr7Response  = new s\Http\PSR7Response;
        $response = $psr7Response(new DiactorosResponse(), $this->response);
        $this->assertInstanceOf(DiactorosResponse::class, $response);
    }
}
