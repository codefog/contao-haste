<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests\Fixtures;

use Contao\Input;
use Contao\Widget;

class FormTextField extends Widget
{
    public function validate(): void
    {
        $this->value = Input::post($this->name);
    }
}
