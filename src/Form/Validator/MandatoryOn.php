<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Form\Validator;

use Codefog\HasteBundle\Form\Form;
use Contao\Widget;

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
        foreach (array_keys($this->matches) as $fieldName) {
            $targetWidget = $form->getWidget($fieldName);
            $targetWidgetValue = $targetWidget->value;

            if ('' === trim($value) && $this->matches[$targetWidget->name] && \in_array($targetWidgetValue, $this->matches[$targetWidget->name], true)) {
                throw new \RuntimeException($GLOBALS['TL_LANG']['MSC']['mandatory']);
            }
        }

        return $value;
    }
}
