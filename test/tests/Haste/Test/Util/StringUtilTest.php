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

include_once __DIR__ . '/../../../../../library/Haste/Util/StringUtil.php';

use Haste\Util\StringUtil;

include_once __DIR__ . '/../../../../../library/Haste/Util/StringUtil.php';


class StringUtilTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param mixed  $value
     * @param string $key
     * @param array  $data
     * @param array  $expected
     *
     * @dataProvider flattenDataProvider
     */
    public function testFlatten($value, $key, array $data, array $expected)
    {
        StringUtil::flatten($value, $key, $data);

        $this->assertEquals(
            $expected,
            $data
        );
    }

    public function flattenDataProvider()
    {
        return array(
            // String input will set key and value
            array(
                'bar',
                'foo',
                array(),
                array(
                    'foo' => 'bar'
                ),
            ),

            // Empty array will be converted to empty string
            array(
                array(),
                'foo',
                array(),
                array(
                    'foo' => ''
                ),
            ),

            // Numeric array keys will add boolean flags
            array(
                array('bar'),
                'foo',
                array(),
                array(
                    'foo_bar' => '1',
                    'foo' => 'bar'
                ),
            ),

            // Multiple values will be comma-separated
            array(
                array('bar', 'baz'),
                'foo',
                array(),
                array(
                    'foo_bar' => '1',
                    'foo_baz' => '1',
                    'foo' => 'bar, baz'
                ),
            ),

            // Array keys are retained
            array(
                array('bar' => 'baz'),
                'foo',
                array(),
                array(
                    'foo_bar' => 'baz',
                    'foo' => '',
                ),
            ),

            // Arrays are handled recursively
            // Array keys are retained
            array(
                array('bar' => array('baz')),
                'foo',
                array(),
                array(
                    'foo_bar' => 'baz',
                    'foo_bar_baz' => '1',
                    'foo' => '',
                ),
            ),
            array(
                array('bar' => array('baz' => 'boo')),
                'foo',
                array(),
                array(
                    'foo_bar_baz' => 'boo',
                    'foo_bar' => '',
                    'foo' => '',
                ),
            ),
            array(
                array('bar' => array('baz' => array('boo'))),
                'foo',
                array(),
                array(
                    'foo_bar_baz_boo' => '1',
                    'foo_bar_baz' => 'boo',
                    'foo_bar' => '',
                    'foo' => '',
                ),
            ),
        );
    }
}
