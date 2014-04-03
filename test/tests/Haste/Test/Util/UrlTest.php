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
        $this->assertSame(Url::addQueryString($queryToAdd, $request), $expectedResult);
    }
    /**
     * @dataProvider removeQueryStringProvider
     */
    public function testRemoveQueryString($request, $paramsToRemove, $expectedResult)
    {
        $this->assertSame(Url::removeQueryString($paramsToRemove, $request), $expectedResult);
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
                'param3=value3',
                'http://domain.com/path.html?param3=value3'
            ),
        );
    }

    public function removeQueryStringProvider()
    {
        // current request -> query to add -> expected result
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
        );
    }
}
