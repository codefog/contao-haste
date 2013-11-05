<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 21.08.13
 * Time: 17:33
 * To change this template use File | Settings | File Templates.
 */

namespace Haste\Test\DateTime;

include_once __DIR__ . '/../../../../../library/Haste/DateTime/ZodiacSign.php';

use Haste\DateTime\ZodiacSign;

class ZodiacSignTest extends \PHPUnit_Framework_TestCase
{

    public function testLatins()
    {
        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-03-21'));
        $this->assertEquals('aries', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-04-20'));
        $this->assertEquals('taurus', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-05-21'));
        $this->assertEquals('gemini', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-06-21'));
        $this->assertEquals('cancer', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-07-23'));
        $this->assertEquals('leo', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-08-23'));
        $this->assertEquals('virgo', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-09-23'));
        $this->assertEquals('libra', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-10-23'));
        $this->assertEquals('scorpio', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-11-22'));
        $this->assertEquals('sagittarius', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-12-22'));
        $this->assertEquals('capricorn', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-01-20'));
        $this->assertEquals('aquarius', $objSign->getLatin());

        $objSign = new ZodiacSign(\DateTime::createFromFormat('Y-m-d', '2000-02-19'));
        $this->assertEquals('pisces', $objSign->getLatin());
    }
}
