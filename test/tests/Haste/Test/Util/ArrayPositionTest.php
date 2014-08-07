<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2013 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Test\Util;

include_once __DIR__ . '/../../../../../library/Haste/Util/ArrayPosition.php';

use Haste\Util\ArrayPosition;


class ArrayPositionTest extends \PHPUnit_Framework_TestCase
{

    public function testFirst()
    {
        $handler = ArrayPosition::first();

        $this->assertEquals(ArrayPosition::FIRST, $handler->position());

        $result = $handler->addToArray(
            array('test1'=>'test', 'test2'=>'test', 'test3'=>'test'),
            array('first'=>'value')
        );

        $this->assertEquals('value', $result['first']);

        $keys = array_keys($result);

        $this->assertEquals('first', $keys[0]);
        $this->assertEquals('test1', $keys[1]);
        $this->assertEquals('test2', $keys[2]);
        $this->assertEquals('test3', $keys[3]);
    }

    public function testLast()
    {
        $handler = ArrayPosition::last();

        $this->assertEquals(ArrayPosition::LAST, $handler->position());

        $result = $handler->addToArray(
            array('test1'=>'test', 'test2'=>'test', 'test3'=>'test'),
            array('last'=>'value')
        );

        $this->assertEquals('value', $result['last']);

        $keys = array_keys($result);

        $this->assertEquals('test1', $keys[0]);
        $this->assertEquals('test2', $keys[1]);
        $this->assertEquals('test3', $keys[2]);
        $this->assertEquals('last', $keys[3]);
    }

    public function testBefore()
    {
        $handler = ArrayPosition::before('test2');

        $this->assertEquals(ArrayPosition::BEFORE, $handler->position());
        $this->assertEquals('test2', $handler->fieldName());

        $result = $handler->addToArray(
            array('test1'=>'test', 'test2'=>'test', 'test3'=>'test'),
            array('before2'=>'value')
        );

        $this->assertEquals('value', $result['before2']);

        $keys = array_keys($result);

        $this->assertEquals('test1', $keys[0]);
        $this->assertEquals('before2', $keys[1]);
        $this->assertEquals('test2', $keys[2]);
        $this->assertEquals('test3', $keys[3]);
    }

    public function testAfter()
    {
        $handler = ArrayPosition::after('test2');

        $this->assertEquals(ArrayPosition::AFTER, $handler->position());
        $this->assertEquals('test2', $handler->fieldName());

        $result = $handler->addToArray(
            array('test1'=>'test', 'test2'=>'test', 'test3'=>'test'),
            array('after2'=>'value')
        );

        $this->assertEquals('value', $result['after2']);

        $keys = array_keys($result);

        $this->assertEquals('test1', $keys[0]);
        $this->assertEquals('test2', $keys[1]);
        $this->assertEquals('after2', $keys[2]);
        $this->assertEquals('test3', $keys[3]);
    }
}
 