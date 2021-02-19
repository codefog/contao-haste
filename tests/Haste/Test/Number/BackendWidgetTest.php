<?php

namespace Haste\Test\Number;

//include_once '/Applications/MAMP/htdocs/contao3/system/initialize.php';
include_once __DIR__ . '/../../../../../library/Haste/Number/BackendWidget.php';

use Haste\Number\BackendWidget;
use PHPUnit\Framework\TestCase;

class BackendWidgetTest extends TestCase
{
    public function testInstance()
    {
        $objWidget = new BackendWidget(array('name'=>'test_number_field'));
        $this->assertInstanceOf('Haste\Number\BackendWidget', $objWidget);
    }

    /**
     * @dataProvider inputProvider
     */
    public function testInput($input, $output, $expectException = false)
    {
        if ($expectException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        \Input::setPost('test_number_field', $input);
        $objWidget = new BackendWidget(array('name'=>'test_number_field', 'value'=>$input));
        $objWidget->validate();

        $this->assertEquals($output, $objWidget->value);
    }

    public function inputProvider()
    {
        return array(
            array('15.00', 150000),
            array('0', 0),
            array('150', 1500000),
            array('-1', -10000),
            array('foobar.00', 0, true),
            array('test', 0, true),
        );
    }
}