<?php

namespace Codefog\Hastebundle\Tests;

use Codefog\HasteBundle\UrlParser;
use PHPUnit\Framework\TestCase;

class UrlParserTest extends TestCase
{
    /**
     * @dataProvider addQueryStringProvider
     */
    public function testAddQueryString($request, $queryToAdd, $expectedResult)
    {
        $parser = new UrlParser();

        $this->assertSame($expectedResult, $parser->addQueryString($queryToAdd, $request));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryString($request, $paramsToRemove, $expectedResult)
    {
        $parser = new UrlParser();

        $this->assertSame($expectedResult, $parser->removeQueryString($paramsToRemove, $request));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryStringCallback($request, $paramsToRemove, $expectedResult)
    {
        $parser = new UrlParser();

        $actualResult = $parser->removeQueryStringCallback(
            static function ($value, $key) use ($paramsToRemove) {
                return !in_array($key, $paramsToRemove, true);
            },
            $request
        );

        $this->assertSame($expectedResult, $actualResult);
    }


    public function addQueryStringProvider()
    {
        // current request -> query to add -> expected result
        return [
            [
                'http://domain.com/path.html?param1=value1&param2=value2',
                'param3=value3',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3',
            ],
            [
                'http://domain.com/path.html',
                'param1=value1',
                'http://domain.com/path.html?param1=value1',
            ],
            [
                'http://domain.com/path.html',
                '&param1=value1',
                'http://domain.com/path.html?param1=value1',
            ],
            [
                'http://domain.com/path.html',
                '&amp;param1=value1',
                'http://domain.com/path.html?param1=value1',
            ],
            [
                'http://domain.com/path.html',
                'param1=value1&',
                'http://domain.com/path.html?param1=value1',
            ],
            [
                'http://domain.com/path.html',
                'param1=value1&amp;',
                'http://domain.com/path.html?param1=value1',
            ],
            [
                'http://domain.com/path.html?',
                'param1=value1',
                'http://domain.com/path.html?param1=value1',
            ],
            [
                'http://domain.com/path.html?',
                '',
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html?param1=value1',
                'param1=value2',
                'http://domain.com/path.html?param1=value2',
            ],
            [
                'http://domain.com/path.html?param1=value1&amp;',
                'param1=value2',
                'http://domain.com/path.html?param1=value2',
            ],
            [
                'http://domain.com/path.html?&amp;param1=value1',
                'param1=value2',
                'http://domain.com/path.html?param1=value2',
            ],
            [
                'http://domain.com/path.html?param1=value1&param2=value2',
                'param3=value3&param4=value4',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4',
            ],
            [
                'http://domain.com/path.html?param1=value1&param2=value2',
                'param3=value3&amp;param4=value4',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4',
            ],
            [
                'http://domain.com/path.html?param1=value1&amp;param2=value2',
                'param3=value3&amp;param4=value4',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4',
            ],
        ];
    }


    public function removeQueryStringProvider()
    {
        // current request -> query to remove -> expected result
        return [
            [
                'http://domain.com/path.html?param1=value1&param2=value2',
                ['param1'],
                'http://domain.com/path.html?param2=value2',
            ],
            [
                'http://domain.com/path.html?param1=value1&param2=value2',
                ['param1', 'param2'],
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3',
                ['param2'],
                'http://domain.com/path.html?param1=value1&param3=value3',
            ],
            [
                'http://domain.com/path.html?param1=value1',
                ['param2'],
                'http://domain.com/path.html?param1=value1',
            ],
            [
                'http://domain.com/path.html',
                ['param1'],
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html',
                [],
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html',
                [''],
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html?',
                ['param1'],
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html?param1=value1&amp;',
                ['param1'],
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html?&amp;param1=value1',
                ['param1'],
                'http://domain.com/path.html',
            ],
            [
                'http://domain.com/path.html?param1=value1&param2=value2',
                ['param3', 'param4'],
                'http://domain.com/path.html?param1=value1&param2=value2',
            ],
        ];
    }
}
