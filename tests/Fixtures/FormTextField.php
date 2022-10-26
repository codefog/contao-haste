<?php

namespace Codefog\HasteBundle\Tests\Fixtures;

class FormTextField extends \Contao\Widget
{
    public function validate(): void
    {
        $this->value = \Contao\Input::post($this->name);
    }
}
