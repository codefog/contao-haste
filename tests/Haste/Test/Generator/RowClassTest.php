<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 21.08.13
 * Time: 17:33
 * To change this template use File | Settings | File Templates.
 */

namespace Haste\Test\Generator;

include_once __DIR__ . '/../../../../../library/Haste/Generator/RowClass.php';

use Haste\Generator\RowClass;

class RowClassTest extends \PHPUnit_Framework_TestCase
{

    public function testKey()
    {
        $arrTest = array(array());
        RowClass::withKey('class')->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'')));

        $arrTest = array(array());
        RowClass::withKey('rowClass')->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('rowClass'=>'')));
    }

    public function testCustom()
    {
        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addCustom('test')->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'test'), array('class'=>'test'), array('class'=>'test')));
    }

    public function testCount()
    {
        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addCount('row_')->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'row_0'), array('class'=>'row_1'), array('class'=>'row_2')));
    }

    public function testFirstLast()
    {
        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addFirstLast()->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'first'), array('class'=>''), array('class'=>'last')));

        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addFirstLast('col_')->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'col_first'), array('class'=>''), array('class'=>'col_last')));
    }

    public function testEvenOdd()
    {
        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addEvenOdd()->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'even'), array('class'=>'odd'), array('class'=>'even')));

        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addEvenOdd('row_')->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'row_even'), array('class'=>'row_odd'), array('class'=>'row_even')));
    }

    public function testArrayKey()
    {
        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addArrayKey()->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'0'), array('class'=>'1'), array('class'=>'2')));

        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addArrayKey('row_')->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'row_0'), array('class'=>'row_1'), array('class'=>'row_2')));

        $arrTest = array('key1'=>array(), 'key2'=>array(), 'key3'=>array());
        RowClass::withKey('class')->addArrayKey()->applyTo($arrTest);
        $this->assertEquals($arrTest, array('key1'=>array('class'=>'key1'), 'key2'=>array('class'=>'key2'), 'key3'=>array('class'=>'key3')));
    }

    public function testGridRows()
    {
        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addGridRows(2)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'row_0 row_even row_first'), array('class'=>'row_0 row_even row_first'), array('class'=>'row_1 row_odd row_last')));

        $arrTest = array(array(), array(), array(), array(), array(), array(), array(), array(), array());
        RowClass::withKey('class')->addGridRows(2)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(
            array('class'=>'row_0 row_even row_first'), array('class'=>'row_0 row_even row_first'),
            array('class'=>'row_1 row_odd'), array('class'=>'row_1 row_odd'),
            array('class'=>'row_2 row_even'), array('class'=>'row_2 row_even'),
            array('class'=>'row_3 row_odd'), array('class'=>'row_3 row_odd'),
            array('class'=>'row_4 row_even row_last')
        ));

        $arrTest = array(array(), array(), array(), array(), array(), array(), array(), array(), array());
        RowClass::withKey('class')->addGridRows(3)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(
            array('class'=>'row_0 row_even row_first'), array('class'=>'row_0 row_even row_first'), array('class'=>'row_0 row_even row_first'),
            array('class'=>'row_1 row_odd'), array('class'=>'row_1 row_odd'), array('class'=>'row_1 row_odd'),
            array('class'=>'row_2 row_even row_last'), array('class'=>'row_2 row_even row_last'), array('class'=>'row_2 row_even row_last')
        ));
    }

    public function testGridCols()
    {
        $arrTest = array(array(), array(), array());
        RowClass::withKey('class')->addGridCols(2)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd col_last'), array('class'=>'col_0 col_even col_first')));

        $arrTest = array(array(), array(), array(), array(), array(), array(), array(), array(), array());
        RowClass::withKey('class')->addGridCols(2)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(
            array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd col_last'),
            array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd col_last'),
            array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd col_last'),
            array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd col_last'),
            array('class'=>'col_0 col_even col_first')
        ));

        $arrTest = array(array(), array(), array(), array(), array(), array(), array(), array(), array());
        RowClass::withKey('class')->addGridCols(3)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(
            array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd'), array('class'=>'col_2 col_even col_last'),
            array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd'), array('class'=>'col_2 col_even col_last'),
            array('class'=>'col_0 col_even col_first'), array('class'=>'col_1 col_odd'), array('class'=>'col_2 col_even col_last')
        ));
    }

    public function testGrid()
    {
        $arrTest = array(array(), array(), array(), array(), array(), array(), array(), array(), array());
        RowClass::withKey('class')->addGridRows(3)->addGridCols(2)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(
            array('class'=>'row_0 row_even row_first col_0 col_even col_first'), array('class'=>'row_0 row_even row_first col_1 col_odd col_last'),
            array('class'=>'row_1 row_odd col_0 col_even col_first'), array('class'=>'row_1 row_odd col_1 col_odd col_last'),
            array('class'=>'row_2 row_even col_0 col_even col_first'), array('class'=>'row_2 row_even col_1 col_odd col_last'),
            array('class'=>'row_3 row_odd col_0 col_even col_first'), array('class'=>'row_3 row_odd col_1 col_odd col_last'),
            array('class'=>'row_4 row_even row_last col_0 col_even col_first')
        ));

        $arrTest = array(array(), array(), array(), array(), array(), array(), array(), array(), array());
        RowClass::withKey('class')->addGridRows(3)->addGridCols(3)->applyTo($arrTest);
        $this->assertEquals($arrTest, array(
            array('class'=>'row_0 row_even row_first col_0 col_even col_first'), array('class'=>'row_0 row_even row_first col_1 col_odd'), array('class'=>'row_0 row_even row_first col_2 col_even col_last'),
            array('class'=>'row_1 row_odd col_0 col_even col_first'), array('class'=>'row_1 row_odd col_1 col_odd'), array('class'=>'row_1 row_odd col_2 col_even col_last'),
            array('class'=>'row_2 row_even row_last col_0 col_even col_first'), array('class'=>'row_2 row_even row_last col_1 col_odd'), array('class'=>'row_2 row_even row_last col_2 col_even col_last')
        ));
    }
}
