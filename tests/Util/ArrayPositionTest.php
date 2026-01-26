<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests\Util;

use Codefog\HasteBundle\Util\ArrayPosition;
use PHPUnit\Framework\TestCase;

final class ArrayPositionTest extends TestCase
{
    public function testFirst(): void
    {
        $handler = ArrayPosition::first();

        $this->assertSame(ArrayPosition::FIRST, $handler->position());

        $result = $handler->addToArray(
            ['test1' => 'test', 'test2' => 'test', 'test3' => 'test'],
            ['first' => 'value'],
        );

        $this->assertSame('value', $result['first']);

        $keys = array_keys($result);

        $this->assertSame('first', $keys[0]);
        $this->assertSame('test1', $keys[1]);
        $this->assertSame('test2', $keys[2]);
        $this->assertSame('test3', $keys[3]);
    }

    public function testLast(): void
    {
        $handler = ArrayPosition::last();

        $this->assertSame(ArrayPosition::LAST, $handler->position());

        $result = $handler->addToArray(
            ['test1' => 'test', 'test2' => 'test', 'test3' => 'test'],
            ['last' => 'value'],
        );

        $this->assertSame('value', $result['last']);

        $keys = array_keys($result);

        $this->assertSame('test1', $keys[0]);
        $this->assertSame('test2', $keys[1]);
        $this->assertSame('test3', $keys[2]);
        $this->assertSame('last', $keys[3]);
    }

    public function testBefore(): void
    {
        $handler = ArrayPosition::before('test2');

        $this->assertSame(ArrayPosition::BEFORE, $handler->position());
        $this->assertSame('test2', $handler->fieldName());

        $result = $handler->addToArray(
            ['test1' => 'test', 'test2' => 'test', 'test3' => 'test'],
            ['before2' => 'value'],
        );

        $this->assertSame('value', $result['before2']);

        $keys = array_keys($result);

        $this->assertSame('test1', $keys[0]);
        $this->assertSame('before2', $keys[1]);
        $this->assertSame('test2', $keys[2]);
        $this->assertSame('test3', $keys[3]);
    }

    public function testAfter(): void
    {
        $handler = ArrayPosition::after('test2');

        $this->assertSame(ArrayPosition::AFTER, $handler->position());
        $this->assertSame('test2', $handler->fieldName());

        $result = $handler->addToArray(
            ['test1' => 'test', 'test2' => 'test', 'test3' => 'test'],
            ['after2' => 'value'],
        );

        $this->assertSame('value', $result['after2']);

        $keys = array_keys($result);

        $this->assertSame('test1', $keys[0]);
        $this->assertSame('test2', $keys[1]);
        $this->assertSame('after2', $keys[2]);
        $this->assertSame('test3', $keys[3]);
    }
}
