<?php

declare(strict_types=1);

namespace Codefog\Hastebundle\Tests\Form;

use Codefog\HasteBundle\Form\Form;
use Codefog\Hastebundle\Tests\Fixtures\Entity;
use Codefog\Hastebundle\Tests\Fixtures\FormTextField;
use Contao\Input;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testInstance(): void
    {
        $this->assertInstanceOf(Form::class, $this->createForm());
    }

    public function testSetFormActionFromUri(): void
    {
        $form = $this->createForm();
        $form->setAction('foobar');

        $this->assertSame('foobar', $form->getAction());
    }

    public function testFormId(): void
    {
        $this->assertSame('my-form-id', $this->createForm()->getFormId());
    }

    public function testIsSubmitted(): void
    {
        $this->assertTrue($this->createForm()->isSubmitted());
        $this->assertFalse($this->createForm(false)->isSubmitted());
    }

    public function testBoundEntity(): void
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

        $entity = new Entity();
        $form->setBoundEntity($entity);

        Input::setPost('pageTitle', 'My page title test');
        Input::setPost('jumpTo', 42);

        if ($form->validate()) {
            $boundEntity = $form->getBoundEntity();

            $this->assertTrue(spl_object_hash($entity) === spl_object_hash($boundEntity));
            $this->assertSame('My page title test', $boundEntity->getPageTitle());
            $this->assertSame(42, $boundEntity->getJumpTo());
        }
    }

    public function testBoundEntityDefaultValues(): void
    {
        $form = $this->createForm(false);

        $entity = new Entity();
        $entity->setPageTitle('My page');
        $entity->setJumpTo(11);
        $form->setBoundEntity($entity);

        $form
            ->addFormField('pageTitle', [
                'inputType' => 'text',
            ])
            ->addFormField('jumpTo', [
                'inputType' => 'text',
            ])
        ;

        $form->createWidgets();

        $this->assertSame('My page', $form->getWidget('pageTitle')->value);
        $this->assertSame(11, $form->getWidget('jumpTo')->value);
    }

    public function testBoundModel(): void
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
            $this->assertSame('My page title test', $boundModel->pageTitle);
            $this->assertSame(42, $boundModel->jumpTo);
        }
    }

    public function testBoundModelDefaultValues(): void
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

        $this->assertSame(13, $form->getWidget('id')->value);
        $this->assertSame('My page', $form->getWidget('pageTitle')->value);
        $this->assertSame(11, $form->getWidget('jumpTo')->value);
    }

    private function createForm(bool $isSubmitted = true): Form
    {
        $GLOBALS['TL_MODELS']['tl_page'] = PageModel::class;
        $GLOBALS['TL_FFL']['text'] = FormTextField::class;

        return new Form('my-form-id', 'POST', static fn () => $isSubmitted);
    }
}
