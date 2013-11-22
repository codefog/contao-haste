<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 21.08.13
 * Time: 17:33
 * To change this template use File | Settings | File Templates.
 */

namespace Haste\Test\Units\Mass;

include_once __DIR__ . '/../../../../../../library/Haste/Units/Converter.php';
include_once __DIR__ . '/../../../../../../library/Haste/Units/Mass/Unit.php';
include_once __DIR__ . '/../../../../../../library/Haste/Units/Mass/Weighable.php';
include_once __DIR__ . '/../../../../../../library/Haste/Units/Mass/Weight.php';
include_once __DIR__ . '/../../../../../../library/Haste/Units/Mass/Scale.php';

use Haste\Units\Mass\Unit;
use Haste\Units\Mass\Scale;
use Haste\Units\Mass\Weight;

class ScaleTest extends \PHPUnit_Framework_TestCase
{

    protected $instance = null;

    public function setUp()
    {
        $this->instance = new Scale();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('\Haste\Units\Mass\Scale', $this->instance);
    }

    public function testBasics()
    {
        $this->instance->add(new Weight(1, Unit::KILOGRAM));
        $this->assertEquals(1, $this->instance->amountIn(UNIT::KILOGRAM));

        $this->instance->add(new Weight(1, Unit::KILOGRAM));
        $this->assertEquals(2, $this->instance->amountIn(UNIT::KILOGRAM));

        $this->instance->add(new Weight(2.5, Unit::KILOGRAM));
        $this->assertEquals(4.5, $this->instance->amountIn(UNIT::KILOGRAM));

        $this->instance->add(new Weight(500, Unit::GRAM));
        $this->assertEquals(5, $this->instance->amountIn(UNIT::KILOGRAM));
    }

    public function testAdvanced()
    {
        $this->instance->add(new Weight(1, Unit::MILIGRAM));
        $this->instance->add(new Weight(1, Unit::GRAM));
        $this->instance->add(new Weight(1, Unit::KILOGRAM));
        $this->instance->add(new Weight(1, Unit::METRICTON));
        $this->instance->add(new Weight(1, Unit::CARAT));
        $this->instance->add(new Weight(1, Unit::OUNCE));
        $this->instance->add(new Weight(1, Unit::POUND));
        $this->instance->add(new Weight(1, Unit::STONE));
        $this->instance->add(new Weight(1, Unit::GRAIN));

        $this->assertEquals(1007833501.3, round($this->instance->amountIn(UNIT::MILIGRAM), 1));
        $this->assertEquals(1007833.5013496266, $this->instance->amountIn(UNIT::GRAM));
        $this->assertEquals(1007.8335013496, $this->instance->amountIn(UNIT::KILOGRAM));
        $this->assertEquals(1.0078335013, $this->instance->amountIn(UNIT::METRICTON));
        $this->assertEquals(5039167.506748132, round($this->instance->amountIn(UNIT::CARAT), 9));
        $this->assertEquals(35549.6825872865, $this->instance->amountIn(UNIT::OUNCE));
        $this->assertEquals(2221.8925361324, $this->instance->amountIn(UNIT::POUND));
        $this->assertEquals(158.7066097058, $this->instance->amountIn(UNIT::STONE));
        $this->assertEquals(15553247.7529270876, $this->instance->amountIn(UNIT::GRAIN));
    }
}