<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests;

use Codefog\HasteBundle\StringParser;
use PHPUnit\Framework\TestCase;

class StringParserTest extends TestCase
{
    /**
     * @dataProvider flattenDataProvider
     */
    public function testFlatten(mixed $value, string $key, array $data, array $expected): void
    {
        $parser = new StringParser();
        $parser->flatten($value, $key, $data);

        $this->assertSame($expected, $data);
    }

    public static function flattenDataProvider(): iterable
    {
        return [
            // String input will set key and value
            [
                'bar',
                'foo',
                [],
                [
                    'foo' => 'bar',
                ],
            ],

            // Empty array will be converted to empty string
            [
                [],
                'foo',
                [],
                [
                    'foo' => '',
                ],
            ],

            // Numeric array keys will add boolean flags
            [
                ['bar'],
                'foo',
                [],
                [
                    'foo_bar' => '1',
                    'foo' => 'bar',
                ],
            ],

            // Multiple values will be comma-separated
            [
                ['bar', 'baz'],
                'foo',
                [],
                [
                    'foo_bar' => '1',
                    'foo_baz' => '1',
                    'foo' => 'bar, baz',
                ],
            ],

            // Array keys are retained
            [
                ['bar' => 'baz'],
                'foo',
                [],
                [
                    'foo_bar' => 'baz',
                    'foo' => '',
                ],
            ],

            // Arrays are handled recursively Array keys are retained
            [
                ['bar' => ['baz']],
                'foo',
                [],
                [
                    'foo_bar_baz' => '1',
                    'foo_bar' => 'baz',
                    'foo' => '',
                ],
            ],
            [
                ['bar' => ['baz' => 'boo']],
                'foo',
                [],
                [
                    'foo_bar_baz' => 'boo',
                    'foo_bar' => '',
                    'foo' => '',
                ],
            ],
            [
                ['bar' => ['baz' => ['boo']]],
                'foo',
                [],
                [
                    'foo_bar_baz_boo' => '1',
                    'foo_bar_baz' => 'boo',
                    'foo_bar' => '',
                    'foo' => '',
                ],
            ],
        ];
    }
}
