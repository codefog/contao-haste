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
    }

    public function testOutput()
    {
        $objResponse = new Response('Foobar');
        $this->assertSame((string) $objResponse, "HTTP/1.1 200 OK\nContent-Type: text/plain; charset=utf-8\nContent-Length: 6\n\nFoobar");
    }
}