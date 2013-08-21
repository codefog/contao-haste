<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 21.08.13
 * Time: 17:33
 * To change this template use File | Settings | File Templates.
 */

namespace Haste\Test;

use Haste\Form;

class FormTest extends \PHPUnit_Framework_TestCase
{
    protected $instance = null;

    public function setUp()
    {
        $this->instance = new Form('someid', 'POST', function() {
            return true;
        });
    }

    public function testInstance()
    {
        $this->assertInstanceOf('\Haste\Form', $this->instance);
    }

    public function testSetFormActionFromUri()
    {
        $strFormAction = 'foobar';
        $this->instance->setFormActionFromUri($strFormAction);

        $this->assertEquals($strFormAction, $this->instance->getFormAction());
    }

    public function testFormId()
    {
        $this->assertEquals('someid', $this->instance->getFormId());
    }
}