<?php

namespace Codefog\HasteBundle\Form\Validator;

use Contao\Widget;
use Codefog\HasteBundle\Form\Form;

class MandatoryOn implements ValidatorInterface
{
    protected array $matches = [];

    public function addMatch(string $strFieldName, mixed $varValue): self
    {
        $this->matches[$strFieldName][] = $varValue;

        return $this;
    }

    public function validate(mixed $value, Widget $widget, Form $form): mixed
    {
        foreach ($this->matches as $fieldName => $values) {
            $targetWidget = $form->getWidget($fieldName);
            $targetWidgetValue = $targetWidget->value;

            if (trim($value) === '' && $this->matches[$targetWidget->name] && in_array($targetWidgetValue, $this->matches[$targetWidget->name])) {
                throw new \RuntimeException($GLOBALS['TL_LANG']['MSC']['mandatory']);
            }
        }

        return $value;
    }
}
