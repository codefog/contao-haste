<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 21.08.13
 * Time: 17:33
 * To change this template use File | Settings | File Templates.
 */

namespace Haste\Test\Unit\Mass;

include_once __DIR__ . '/../../../../../../library/Haste/Units/Converter.php';
include_once __DIR__ . '/../../../../../../library/Haste/Units/Mass/Unit.php';

use Haste\Units\Mass\Unit;

class UnitTest extends \PHPUnit_Framework_TestCase
{

    public function testBase()
    {
        $this->assertEquals('kg', Unit::getBase());
    }

    public function testConvertFromKilogram()
    {
        $this->assertEquals(1000000, Unit::convert(1, Unit::KILOGRAM, UNIT::MILIGRAM));
        $this->assertEquals(1000, Unit::convert(1, Unit::KILOGRAM, UNIT::GRAM));
        $this->assertEquals(1, Unit::convert(1, Unit::KILOGRAM, UNIT::KILOGRAM));
        $this->assertEquals(0.001, Unit::convert(1, Unit::KILOGRAM, UNIT::METRICTON));
        $this->assertEquals(5000, Unit::convert(1, Unit::KILOGRAM, UNIT::CARAT));
        $this->assertEquals(35.27337, round(Unit::convert(1, Unit::KILOGRAM, UNIT::OUNCE), 5));
        $this->assertEquals(2.2046226218487757, Unit::convert(1, Unit::KILOGRAM, UNIT::POUND));
        $this->assertEquals(0.157473, round(Unit::convert(1, Unit::KILOGRAM, UNIT::STONE), 6));
        $this->assertEquals(15432.3584, round(Unit::convert(1, Unit::KILOGRAM, UNIT::GRAIN), 4));
    }

    public function testConvertFromPounds()
    {
        $this->assertEquals(453592.37, round(Unit::convert(1, Unit::POUND, UNIT::MILIGRAM), 2));
        $this->assertEquals(453.6, round(Unit::convert(1, Unit::POUND, UNIT::GRAM), 1));
        $this->assertEquals(0.45359237, Unit::convert(1, Unit::POUND, UNIT::KILOGRAM));
        $this->assertEquals(0.0004536, round(Unit::convert(1, Unit::POUND, UNIT::METRICTON), 7));
        $this->assertEquals(2268, round(Unit::convert(1, Unit::POUND, UNIT::CARAT), 0));
        $this->assertEquals(16, round(Unit::convert(1, Unit::POUND, UNIT::OUNCE), 0));
        $this->assertEquals(1, Unit::convert(1, Unit::POUND, UNIT::POUND));
        $this->assertEquals(0.0714285714, round(Unit::convert(1, Unit::POUND, UNIT::STONE), 10));
        $this->assertEquals(7000, round(Unit::convert(1, Unit::POUND, UNIT::GRAIN), 0));
    }
}