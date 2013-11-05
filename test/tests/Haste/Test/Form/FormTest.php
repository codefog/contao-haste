<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 21.08.13
 * Time: 17:33
 * To change this template use File | Settings | File Templates.
 */

namespace Haste\Test\Form;

include_once __DIR__ . '/../../../../../library/Haste/Form/Form.php';

use Haste\Form\Form;

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
        $this->assertInstanceOf('\Haste\Form\Form', $this->instance);
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

    public function testIsSubmitted()
    {
        $this->assertTrue($this->instance->isSubmitted());

        $objForm = new Form('someid', 'POST', function() {
            return false;
        });

        $this->assertFalse($objForm->isSubmitted());
    }

    public function testBindModel()
    {
        $this->instance
            ->addFormField('pageTitle', array(
            'inputType'     => 'text'
            ))
            ->addFormField('jumpTo', array(
            'inputType'     => 'text'
        ));

        $objModel = new \PageModel();
        $this->instance->bindModel($objModel);

        $_POST['pageTitle'] = 'My page title test';
        $_POST['jumpTo'] = 42;

        if ($this->instance->validate()) {
            $objBoundModel = $this->instance->getBoundModel();
            $this->assertTrue(spl_object_hash($objModel) === spl_object_hash($objBoundModel));
            $this->assertEquals($objBoundModel->pageTitle, 'My page title test');
            $this->assertEquals($objBoundModel->jumpTo, 42);
        }
    }

    public function testBindModelDefaultValues()
    {
        $objInstance = new Form('someid', 'POST', function() {
            return false;
        });

        $objModel = \PageModel::findByPk(13);
        $objInstance->bindModel($objModel);

        $objInstance
            ->addFormField('id', array(
                'inputType'     => 'text'
            ))
            ->addFormField('pageTitle', array(
                'inputType'     => 'text'
            ))
            ->addFormField('jumpTo', array(
                'inputType'     => 'text'
            ));

        $objInstance->createWidgets();

        $this->assertEquals($objInstance->getWidget('id')->value, 13);
        $this->assertEquals($objInstance->getWidget('pageTitle')->value, 'My page');
        $this->assertEquals($objInstance->getWidget('jumpTo')->value, 11);
    }
}