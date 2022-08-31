<?php

namespace Codefog\Hastebundle\Tests\Form;

use Codefog\HasteBundle\Form\Form;
use Codefog\Hastebundle\Tests\Fixtures\FormTextField;
use Contao\Input;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf(Form::class, $this->createForm());
    }

    public function testSetFormActionFromUri()
    {
        $form = $this->createForm();
        $form->setAction('foobar');

        $this->assertEquals('foobar', $form->getAction());
    }

    public function testFormId()
    {
        $this->assertEquals('my-form-id', $this->createForm()->getFormId());
    }

    public function testIsSubmitted()
    {
        $this->assertTrue($this->createForm()->isSubmitted());
        $this->assertFalse($this->createForm(false)->isSubmitted());
    }

    public function testBoundModel()
    {
        $form = $this->createForm();

        $form
            ->addFormField('pageTitle', [
                'inputType' => 'text',
            ])
            ->addFormField('jumpTo', [
                'inputType' => 'text',
            ])
        ;

        $pageModel = new PageModel();
        $form->setBoundModel($pageModel);

        Input::setPost('pageTitle', 'My page title test');
        Input::setPost('jumpTo', 42);

        if ($form->validate()) {
            $boundModel = $form->getBoundModel();

            $this->assertTrue(spl_object_hash($pageModel) === spl_object_hash($boundModel));
            $this->assertEquals('My page title test', $boundModel->pageTitle);
            $this->assertEquals(42, $boundModel->jumpTo);
        }
    }

    public function testBoundModelDefaultValues()
    {
        $form = $this->createForm(false);

        $pageModel = PageModel::findByPk(13);
        $pageModel->pageTitle = 'My page';
        $pageModel->jumpTo = 11;
        $form->setBoundModel($pageModel);

        $form
            ->addFormField('id', [
                'inputType' => 'text',
            ])
            ->addFormField('pageTitle', [
                'inputType' => 'text',
            ])
            ->addFormField('jumpTo', [
                'inputType' => 'text',
            ])
        ;

        $form->createWidgets();

        $this->assertEquals(13, $form->getWidget('id')->value);
        $this->assertEquals('My page', $form->getWidget('pageTitle')->value);
        $this->assertEquals(11, $form->getWidget('jumpTo')->value);
    }

    private function createForm(bool $isSubmitted = true): Form
    {
        $GLOBALS['TL_MODELS']['tl_page'] = PageModel::class;
        $GLOBALS['TL_FFL']['text'] = FormTextField::class;

        return new Form('my-form-id', 'POST', fn () => $isSubmitted);
    }
}
