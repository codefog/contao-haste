<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests;

use Codefog\HasteBundle\UrlParser;
use PHPUnit\Framework\TestCase;

class UrlParserTest extends TestCase
{
    /**
     * @dataProvider addQueryStringProvider
     */
    public function testAddQueryString($request, $queryToAdd, $expectedResult): void
    {
        $parser = new UrlParser();

        $this->assertSame($expectedResult, $parser->addQueryString($queryToAdd, $request));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryString($request, $paramsToRemove, $expectedResult): void
    {
        $parser = new UrlParser();

        $this->assertSame($expectedResult, $parser->removeQueryString($paramsToRemove, $request));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryStringCallback($request, $paramsToRemove, $expectedResult): void
    {
        $parser = new UrlParser();

        $actualResult = $parser->removeQueryStringCallback(
            static fn ($value, $key) => !\in_array($key, $paramsToRemove, true),
            $request,
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    public static function addQueryStringProvider(): iterable
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

    public static function removeQueryStringProvider(): iterable
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
