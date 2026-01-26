<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests;

use Codefog\HasteBundle\StringParser;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\SimpleTokenParser;
use PHPUnit\Framework\TestCase;

final class StringParserTest extends TestCase
{
    /**
     * @dataProvider flattenDataProvider
     */
    public function testFlatten(mixed $value, string $key, array $data, array $expected): void
    {
        $parser = new StringParser($this->createMock(SimpleTokenParser::class), $this->createMock(InsertTagParser::class));
        $parser->flatten($value, $key, $data);

        $this->assertSame($expected, $data);
    }

    public static function flattenDataProvider(): iterable
    {
        // String input will set key and value
        yield [
            'bar',
            'foo',
            [],
            [
                'foo' => 'bar',
            ],
        ];

        // Empty array will be converted to empty string
        yield [
            [],
            'foo',
            [],
            [
                'foo' => '',
            ],
        ];

        // Numeric array keys will add boolean flags
        yield [
            ['bar'],
            'foo',
            [],
            [
                'foo_bar' => '1',
                'foo' => 'bar',
            ],
        ];

        // Multiple values will be comma-separated
        yield [
            ['bar', 'baz'],
            'foo',
            [],
            [
                'foo_bar' => '1',
                'foo_baz' => '1',
                'foo' => 'bar, baz',
            ],
        ];

        // Array keys are retained
        yield [
            ['bar' => 'baz'],
            'foo',
            [],
            [
                'foo_bar' => 'baz',
                'foo' => '',
            ],
        ];

        // Arrays are handled recursively Array keys are retained
        yield [
            ['bar' => ['baz']],
            'foo',
            [],
            [
                'foo_bar_baz' => '1',
                'foo_bar' => 'baz',
                'foo' => '',
            ],
        ];

        yield [
            ['bar' => ['baz' => 'boo']],
            'foo',
            [],
            [
                'foo_bar_baz' => 'boo',
                'foo_bar' => '',
                'foo' => '',
            ],
        ];

        yield [
            ['bar' => ['baz' => ['boo']]],
            'foo',
            [],
            [
                'foo_bar_baz_boo' => '1',
                'foo_bar_baz' => 'boo',
                'foo_bar' => '',
                'foo' => '',
            ],
        ];
    }
}
