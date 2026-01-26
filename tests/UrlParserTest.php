<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests;

use Codefog\HasteBundle\UrlParser;
use PHPUnit\Framework\TestCase;

final class UrlParserTest extends TestCase
{
    /**
     * @dataProvider addQueryStringProvider
     */
    public function testAddQueryString(string $url, string $queryToAdd, string $expectedResult): void
    {
        $parser = new UrlParser();

        $this->assertSame($expectedResult, $parser->addQueryString($queryToAdd, $url));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryString(string $url, array $paramsToRemove, string $expectedResult): void
    {
        $parser = new UrlParser();

        $this->assertSame($expectedResult, $parser->removeQueryString($paramsToRemove, $url));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryStringCallback(string $url, array $paramsToRemove, string $expectedResult): void
    {
        $parser = new UrlParser();

        $actualResult = $parser->removeQueryStringCallback(
            static fn ($value, $key) => !\in_array($key, $paramsToRemove, true),
            $url,
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    public static function addQueryStringProvider(): iterable
    {
        // current request -> query to add -> expected result
        yield [
            'http://domain.com/path.html?param1=value1&param2=value2',
            'param3=value3',
            'http://domain.com/path.html?param1=value1&param2=value2&param3=value3',
        ];

        yield [
            'http://domain.com/path.html',
            'param1=value1',
            'http://domain.com/path.html?param1=value1',
        ];

        yield [
            'http://domain.com/path.html',
            '&param1=value1',
            'http://domain.com/path.html?param1=value1',
        ];

        yield [
            'http://domain.com/path.html',
            '&amp;param1=value1',
            'http://domain.com/path.html?param1=value1',
        ];

        yield [
            'http://domain.com/path.html',
            'param1=value1&',
            'http://domain.com/path.html?param1=value1',
        ];

        yield [
            'http://domain.com/path.html',
            'param1=value1&amp;',
            'http://domain.com/path.html?param1=value1',
        ];

        yield [
            'http://domain.com/path.html?',
            'param1=value1',
            'http://domain.com/path.html?param1=value1',
        ];

        yield [
            'http://domain.com/path.html?',
            '',
            'http://domain.com/path.html',
        ];

        yield [
            'http://domain.com/path.html?param1=value1',
            'param1=value2',
            'http://domain.com/path.html?param1=value2',
        ];

        yield [
            'http://domain.com/path.html?param1=value1&amp;',
            'param1=value2',
            'http://domain.com/path.html?param1=value2',
        ];

        yield [
            'http://domain.com/path.html?&amp;param1=value1',
            'param1=value2',
            'http://domain.com/path.html?param1=value2',
        ];

        yield [
            'http://domain.com/path.html?param1=value1&param2=value2',
            'param3=value3&param4=value4',
            'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4',
        ];

        yield [
            'http://domain.com/path.html?param1=value1&param2=value2',
            'param3=value3&amp;param4=value4',
            'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4',
        ];

        yield [
            'http://domain.com/path.html?param1=value1&amp;param2=value2',
            'param3=value3&amp;param4=value4',
            'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4',
        ];
    }

    public static function removeQueryStringProvider(): iterable
    {
        // current request -> query to remove -> expected result
        yield [
            'http://domain.com/path.html?param1=value1&param2=value2',
            ['param1'],
            'http://domain.com/path.html?param2=value2',
        ];
        yield [
            'http://domain.com/path.html?param1=value1&param2=value2',
            ['param1', 'param2'],
            'http://domain.com/path.html',
        ];
        yield [
            'http://domain.com/path.html?param1=value1&param2=value2&param3=value3',
            ['param2'],
            'http://domain.com/path.html?param1=value1&param3=value3',
        ];
        yield [
            'http://domain.com/path.html?param1=value1',
            ['param2'],
            'http://domain.com/path.html?param1=value1',
        ];
        yield [
            'http://domain.com/path.html',
            ['param1'],
            'http://domain.com/path.html',
        ];
        yield [
            'http://domain.com/path.html',
            [],
            'http://domain.com/path.html',
        ];
        yield [
            'http://domain.com/path.html',
            [''],
            'http://domain.com/path.html',
        ];
        yield [
            'http://domain.com/path.html?',
            ['param1'],
            'http://domain.com/path.html',
        ];
        yield [
            'http://domain.com/path.html?param1=value1&amp;',
            ['param1'],
            'http://domain.com/path.html',
        ];
        yield [
            'http://domain.com/path.html?&amp;param1=value1',
            ['param1'],
            'http://domain.com/path.html',
        ];
        yield [
            'http://domain.com/path.html?param1=value1&param2=value2',
            ['param3', 'param4'],
            'http://domain.com/path.html?param1=value1&param2=value2',
        ];
    }
}
