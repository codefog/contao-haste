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

namespace Haste\Test\DateTime;

use Haste\DateTime\DateTime;

include_once __DIR__ . '/../../../../../library/Haste/DateTime/DateTime.php';

class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromFormat()
    {
        $time = time();

        $native = \DateTime::createFromFormat('U', $time);
        $haste  = DateTime::createFromFormat('U', $time);

        static::assertEquals($native->format('c'), $haste->format('c'));
        static::assertInstanceOf('Haste\DateTime\DateTime', $haste);
    }

    public function testCreateFromFormatInTimezone()
    {
        $native = \DateTime::createFromFormat('Y-m-d H:i', '2016-24-31 12:42', new \DateTimeZone('America/Porto_Acre'));
        $haste  = DateTime::createFromFormat('Y-m-d H:i', '2016-24-31 12:42', new \DateTimeZone('America/Porto_Acre'));

        static::assertEquals($native->format('c'), $haste->format('c'));
        static::assertInstanceOf('Haste\DateTime\DateTime', $haste);
    }
}
