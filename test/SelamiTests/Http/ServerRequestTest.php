<?php

namespace tests;

use Selami as s;
use Zend\Diactoros\ServerRequest as DiactorosRequest;
use UnexpectedValueException;

class MyServerRequestClass extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__."/htdocs";
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME'] = 'selamy';
        $_SERVER['SERVER_PORT'] = '8080';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'p1=1&p2=2';
        $_SERVER['HTTPS'] = '';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_GET       = ['var_1' => 1, 'var_2' => 2, 'var_3' =>  3];
        $_POST      = ['var_4' => 4, 'var_5' => 5, 'var_3' =>  6];
        $_COOKIE    = [];
        $_FILES     = [];
    }

    /**
     * @test
     */
    public function shouldBeDiactorosInstance()
    {
        $request = s\Http\ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $this->assertInstanceOf(DiactorosRequest::class, $request);
    }

    /**
     * @test
     */
    public function shouldGetParamSuccessfully()
    {
        $request = s\Http\ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $var_1= $request->getParam('var_1');
        $var_3 = $request->getParam('var_3');
        $this->assertEquals(1, $var_1);
        $this->assertEquals(6, $var_3);
    }
    /**
     * @test
     */
    public function shouldGetParamsSuccessfully()
    {
        $request = s\Http\ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $params = $request->getParams();
        $this->assertEquals(5, count($params));
    }
    /**
     * @test
     */
    public function shouldReturnDefaultsParamsSuccessfully()
    {
        unset($_SERVER['SERVER_PROTOCOL']);
        $request = s\Http\ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $params = $request->getParams();
        $this->assertEquals(5, count($params));
        $_SERVER['SERVER_PROTOCOL'] = 'None';
        $this->expectException(UnexpectedValueException::class);
        s\Http\ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

    }



}
