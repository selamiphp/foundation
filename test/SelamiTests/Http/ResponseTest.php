<?php

namespace tests;

use Selami as s;
use Selami\Router;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response as DiactorosResponse;

class MyResponseClass extends TestCase
{
    private $response   = [
        'statusCode'    => 200,
        'headers'       => ['X-TEST' => '1'],
        'cookies'       => null,
        'body'          => '<html><body><p>Test</p></body></html>',
        'textBody'      => 'Test Text',
        'data'          => ['status' => 200, 'data' => ['health' => 'OK']],
        'contentType'   => Router::HTML,
        'redirect'      => 'http://127.0.0.1:8080/redirect'
    ];

    /**
     * @test
     */
    public function shouldReturnHTMLResponse()
    {
        $response = new s\Http\Response();
        $response->setHeaders($this->response['headers']);
        $response->setStatusCode($this->response['statusCode']);
        $response->setOutputType(Router::HTML);
        $response->setBody($this->response['body']);
        $this->assertArrayHasKey('X-TEST', $response->getHeaders());
        $this->assertContains('<p>Test</p>', $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(Router::HTML, $response->getOutputType());
        $response->setOutputType(99);
        $this->assertEquals(Router::HTML, $response->getOutputType());
        $response->send();
        $this->expectOutputRegex('#<p>Test</p>#msi');
        $this->assertArrayHasKey('Content-Type', $response->getHeaders());
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaders()['Content-Type']);
    }

    /**
     * @test
     */
    public function shouldReturnJSONResponse()
    {
        $response = new s\Http\Response();
        $response->setHeaders($this->response['headers']);
        $response->setStatusCode($this->response['statusCode']);
        $response->setOutputType(Router::JSON);
        $response->setData($this->response['data']);
        $this->assertEquals(Router::JSON, $response->getOutputType());
        $output  = json_decode($response->getBody(), true);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertArrayHasKey('status', $output);
        $this->assertArrayHasKey('data', $output);
        $this->assertEquals('OK', $output['data']['health']);
        $response->send();
        $this->expectOutputString('{"status":200,"data":{"health":"OK"}}');
        $this->assertArrayHasKey('Content-Type', $response->getHeaders());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaders()['Content-Type']);
    }


    /**
     * @test
     */
    public function shouldReturnTextResponse()
    {
        $response = new s\Http\Response();
        $response->setHeaders($this->response['headers']);
        $response->setStatusCode($this->response['statusCode']);
        $response->setOutputType(Router::TEXT);
        $response->setBody($this->response['textBody']);
        $this->assertEquals(ROUTER::TEXT, $response->getOutputType());
        $this->assertContains('Test', $response->getBody());
        $this->assertContains('Text', $response->getBody());
        $response->send();
        $this->expectOutputString('Test Text');
        $this->assertArrayHasKey('Content-Type', $response->getHeaders());
        $this->assertEquals('text/plain; charset=UTF-8', $response->getHeaders()['Content-Type']);
    }

    /**
     * @test
     */
    public function shouldRedirect()
    {
        $response = new s\Http\Response();
        $response->setHeaders($this->response['headers']);
        $response->setStatusCode($this->response['statusCode']);
        $response->setOutputType(Router::REDIRECT);
        $response->setRedirect($this->response['redirect']);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://127.0.0.1:8080/redirect', $response->getRedirectUri());
        $response->send();
        $this->expectOutputString('');
    }



    public function testPsr7Response()
    {
        $psr7Response  = new s\Http\Psr7Response;
        $response = $psr7Response(new DiactorosResponse(), $this->response);
        $this->assertInstanceOf(DiactorosResponse::class, $response);
        $this->response['contentType']='redirect';
        $psr7Response  = new s\Http\PSR7Response;
        $response = $psr7Response(new DiactorosResponse(), $this->response);
        $this->assertInstanceOf(DiactorosResponse::class, $response);
        $this->response['contentType']=Router::JSON;
        $psr7Response  = new s\Http\PSR7Response;
        $response = $psr7Response(new DiactorosResponse(), $this->response);
        $this->assertInstanceOf(DiactorosResponse::class, $response);
    }
}
