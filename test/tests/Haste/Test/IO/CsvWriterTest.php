<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 21.08.13
 * Time: 17:33
 * To change this template use File | Settings | File Templates.
 */

namespace Haste\Test\DateTime;

include_once __DIR__ . '/../../../../../library/Haste/IO/Reader/HeaderFieldsInterface.php';
include_once __DIR__ . '/../../../../../library/Haste/IO/Reader/ArrayReader.php';
include_once __DIR__ . '/../../../../../library/Haste/IO/Writer/WriterInterface.php';
include_once __DIR__ . '/../../../../../library/Haste/IO/Writer/AbstractWriter.php';
include_once __DIR__ . '/../../../../../library/Haste/IO/Writer/AbstractFileWriter.php';
include_once __DIR__ . '/../../../../../library/Haste/IO/Writer/CsvFileWriter.php';

use Haste\IO\Reader\ArrayReader;
use Haste\IO\Writer\CsvFileWriter;

class CsvFileWriterTest extends \PHPUnit_Framework_TestCase
{

    protected $tempFile;


    public function setUp()
    {
        $file = tempnam(sys_get_temp_dir(), '');

        define(TL_ROOT, dirname($file));
        $this->tempFile = basename($file);
    }


    /**
     * @dataProvider arrayDataProvider
     */
    public function testArrayData(array $testData, $delimiter, $expectedResult)
    {
        $objReader = new \Haste\IO\Reader\ArrayReader($testData);

        $objWriter = new \Haste\IO\Writer\CsvFileWriter($this->tempFile);
        $objWriter->setDelimiter($delimiter);
        $objWriter->writeFrom($objReader);

        $this->assertEquals($expectedResult, file_get_contents(TL_ROOT . '/' . $this->tempFile));
    }


    public function arrayDataProvider()
    {
        return array(
            // Empty values
            array(array(), ''),

            // Comma separated
            array(
                array(
                    array('value1', 'value2', 'value3'),
                    array('value4', 'value5', 'value6'),
                    array('value7', 'value8', 'value9'),
                ),
                ',',
                "value1,value2,value3\nvalue4,value5,value6\nvalue7,value8,value9\n"
            ),

            // Semicolon separated
            array(
                array(
                    array('value1', 'value2', 'value3'),
                    array('value4', 'value5', 'value6'),
                    array('value7', 'value8', 'value9'),
                ),
                ';',
                "value1;value2;value3\nvalue4;value5;value6\nvalue7;value8;value9\n"
            ),

            // With enclosure
            array(
                array(
                    array('value"1"', 'value2', 'value3'),
                    array('value4', 'value5', 'value6'),
                    array('value7', 'value8', 'value9'),
                ),
                ',',
                '"value""1""",value2,value3'."\nvalue4,value5,value6\nvalue7,value8,value9\n"
            ),
        );
    }
}
