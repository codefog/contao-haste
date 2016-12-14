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

include_once __DIR__ . '/../../../../../library/Haste/Util/Url.php';

use Haste\Util\Url;

class UrlTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider addQueryStringProvider
     */
    public function testAddQueryString($request, $queryToAdd, $expectedResult)
    {
        $this->assertSame($expectedResult, Url::addQueryString($queryToAdd, $request));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryString($request, $paramsToRemove, $expectedResult)
    {
        $this->assertSame($expectedResult, Url::removeQueryString($paramsToRemove, $request));
    }

    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryStringCallback($request, $paramsToRemove, $expectedResult)
    {
        $actualResult = Url::removeQueryStringCallback(
            function ($value, $key) use ($paramsToRemove) {
                return !in_array($key, $paramsToRemove, true);
            },
            $request
        );

        $this->assertSame($expectedResult, $actualResult);
    }


    public function addQueryStringProvider()
    {
        // current request -> query to add -> expected result
        return array(
            array(
                'http://domain.com/path.html?param1=value1&param2=value2',
                'param3=value3',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3'
            ),
            array(
                'http://domain.com/path.html',
                'param1=value1',
                'http://domain.com/path.html?param1=value1'
            ),
            array(
                'http://domain.com/path.html',
                '&param1=value1',
                'http://domain.com/path.html?param1=value1'
            ),
            array(
                'http://domain.com/path.html',
                '&amp;param1=value1',
                'http://domain.com/path.html?param1=value1'
            ),
            array(
                'http://domain.com/path.html',
                'param1=value1&',
                'http://domain.com/path.html?param1=value1'
            ),
            array(
                'http://domain.com/path.html',
                'param1=value1&amp;',
                'http://domain.com/path.html?param1=value1'
            ),
            array(
                'http://domain.com/path.html?',
                'param1=value1',
                'http://domain.com/path.html?param1=value1'
            ),
            array(
                'http://domain.com/path.html?',
                '',
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html?param1=value1',
                'param1=value2',
                'http://domain.com/path.html?param1=value2'
            ),
            array(
                'http://domain.com/path.html?param1=value1&amp;',
                'param1=value2',
                'http://domain.com/path.html?param1=value2'
            ),
            array(
                'http://domain.com/path.html?&amp;param1=value1',
                'param1=value2',
                'http://domain.com/path.html?param1=value2'
            ),
            array(
                'http://domain.com/path.html?param1=value1&param2=value2',
                'param3=value3&param4=value4',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4'
            ),
            array(
                'http://domain.com/path.html?param1=value1&param2=value2',
                'param3=value3&amp;param4=value4',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4'
            ),
            array(
                'http://domain.com/path.html?param1=value1&amp;param2=value2',
                'param3=value3&amp;param4=value4',
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3&param4=value4'
            ),
        );
    }


    public function removeQueryStringProvider()
    {
        // current request -> query to remove -> expected result
        return array(
            array(
                'http://domain.com/path.html?param1=value1&param2=value2',
                array('param1'),
                'http://domain.com/path.html?param2=value2'
            ),
            array(
                'http://domain.com/path.html?param1=value1&param2=value2',
                array('param1', 'param2'),
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html?param1=value1&param2=value2&param3=value3',
                array('param2'),
                'http://domain.com/path.html?param1=value1&param3=value3'
            ),
            array(
                'http://domain.com/path.html?param1=value1',
                array('param2'),
                'http://domain.com/path.html?param1=value1'
            ),
            array(
                'http://domain.com/path.html',
                array('param1'),
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html',
                array(),
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html',
                array(''),
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html?',
                array('param1'),
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html?param1=value1&amp;',
                array('param1'),
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html?&amp;param1=value1',
                array('param1'),
                'http://domain.com/path.html'
            ),
            array(
                'http://domain.com/path.html?param1=value1&param2=value2',
                array('param3', 'param4'),
                'http://domain.com/path.html?param1=value1&param2=value2'
            ),
        );
    }
}
