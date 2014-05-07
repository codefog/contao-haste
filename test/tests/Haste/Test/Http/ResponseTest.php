<?php

namespace Haste\Test\Http;

include_once __DIR__ . '/../../../../../library/Haste/Http/Response/Response.php';
include_once __DIR__ . '/../../../../../library/Haste/Http/Response/JsonResponse.php';
include_once __DIR__ . '/../../../../../library/Haste/Http/Response/XmlResponse.php';
include_once __DIR__ . '/../../../../../library/Haste/Http/Response/HtmlResponse.php';
include_once __DIR__ . '/../../../../../library/Haste/Util/InsertTag.php';
include_once __DIR__ . '/../../../../../library/Haste/Haste.php';

use Haste\Http\Response\Response;
use Haste\Http\Response\JsonResponse;
use Haste\Http\Response\XmlResponse;
use Haste\Http\Response\HtmlResponse;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $objResponse = new Response('Foobar');
        $this->assertInstanceOf('Haste\Http\Response\Response', $objResponse);
    }

    public function testStatusCode()
    {
        $objResponse = new Response('Foobar');
        $this->assertSame(200, $objResponse->getStatusCode());
        $objResponse = new Response('Foobar', 500);
        $this->assertSame(500, $objResponse->getStatusCode());
    }

    public function testRegularOutput()
    {
        $objResponse = new Response('Foobar');
        $this->assertSame("HTTP/1.1 200 OK\nContent-Length: 6\nContent-Type: text/plain; charset=utf-8\n\nFoobar", (string) $objResponse);
    }

    public function testJsonOutput()
    {
        $objResponse = new JsonResponse(array('foo' => 'bar'), 201);
        $this->assertSame(201, $objResponse->getStatusCode());
        $this->assertSame("HTTP/1.1 201 Created\nContent-Length: 13\nContent-Type: application/json; charset=utf-8\n\n{\"foo\":\"bar\"}", (string) $objResponse);
    }

    public function testEmptyJsonOutput()
    {
        $objResponse = new JsonResponse(array());
        $this->assertSame("HTTP/1.1 200 OK\nContent-Length: 2\nContent-Type: application/json; charset=utf-8\n\n[]", (string) $objResponse);
    }

    public function testXmlOutput()
    {
        $objResponse = new XmlResponse('<foo><bar>indeed</bar></foo>', 304);
        $this->assertSame(304, $objResponse->getStatusCode());
        $this->assertSame("HTTP/1.1 304 Not Modified\nContent-Length: 28\nContent-Type: application/xml; charset=utf-8\n\n<foo><bar>indeed</bar></foo>", (string) $objResponse);
    }

    public function testHtmlOutput()
    {
        $objResponse = new HtmlResponse('<!DOCTYPE html><html lang="en"><head></head><body></body></html>', 100);
        $this->assertSame(100, $objResponse->getStatusCode());
        $this->assertSame((string) $objResponse, "HTTP/1.1 100 Continue\nContent-Length: 64\nContent-Type: text/html; charset=utf-8\n\n<!DOCTYPE html><html lang=\"en\"><head></head><body></body></html>");
    }

    public function testSetStatusCode()
    {
        $objResponse = new Response('Foobar', 500);
        $this->assertSame(500, $objResponse->getStatusCode());
        $objResponse->setStatusCode(200);
        $this->assertSame(200, $objResponse->getStatusCode());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetStatusCodeException()
    {
        $objResponse = new Response('Foobar', 200);
        $objResponse->setStatusCode(7000);
    }
}