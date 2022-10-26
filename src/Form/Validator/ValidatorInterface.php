<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Form\Validator;

use Codefog\HasteBundle\Form\Form;
use Contao\Widget;

interface ValidatorInterface
{
    /**
     * Validates a widget.
     */
    public function validate(mixed $value, Widget $widget, Form $form): mixed;
}
