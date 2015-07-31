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

include_once __DIR__ . '/../../../../../library/Haste/Util/RepositoryVersion.php';

use Haste\Util\RepositoryVersion;

class RepositoryVersionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider versionProvider
     */
    public function testFormat($expected, $input)
    {
        $this->assertEquals($expected, RepositoryVersion::format($input));
    }

    /**
     * @dataProvider versionProvider
     */
    public function testEncode($input, $expected)
    {
        $this->assertEquals($expected, RepositoryVersion::encode($input));
    }

    /**
     * @dataProvider semverProvider
     */
    public function testEncodeSemver($input, $expected)
    {
        $this->assertEquals($expected, RepositoryVersion::encode($input));
    }


    public function versionProvider()
    {
        return array(
            array('1.1.0 stable', '10010009'),
            array('2.5.0 stable', '20050009'),
            array('2.5.7 stable', '20050079'),
            array('22.55.77 stable', '220550779'),
            array('1.0.999 stable', '10009999'),
            array('1.0.0 alpha1', '10000000'),
            array('1.0.0 alpha2', '10000001'),
            array('1.0.0 alpha3', '10000002'),
            array('1.0.0 beta1', '10000003'),
            array('1.0.0 beta2', '10000004'),
            array('1.0.0 beta3', '10000005'),
            array('1.0.0 RC1', '10000006'),
            array('1.0.0 RC2', '10000007'),
            array('1.0.0 RC3', '10000008'),
            array('1.0.0 stable', '10000009'),
        );
    }

    public function semverProvider()
    {
        return array(
            array('1.1.0', '10010009'),
            array('2.5.0', '20050009'),
            array('2.5.7', '20050079'),
            array('22.55.77', '220550779'),
            array('1.0.999', '10009999'),
            array('1.0.0-alpha1', '10000000'),
            array('1.0.0-alpha2', '10000001'),
            array('1.0.0-alpha3', '10000002'),
            array('1.0.0-beta1', '10000003'),
            array('1.0.0-beta2', '10000004'),
            array('1.0.0-beta3', '10000005'),
            array('1.0.0-RC1', '10000006'),
            array('1.0.0-RC2', '10000007'),
            array('1.0.0-RC3', '10000008'),
            array('1.0.0-stable', '10000009'),
        );
    }
}
