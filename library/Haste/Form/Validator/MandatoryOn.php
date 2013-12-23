<?php

namespace Haste\Form\Validator;


class MandatoryOn implements ValidatorInterface
{
    /**
     * Matches
     * @var array
     */
    protected $arrMatches = array();


    /**
     * Add match
     * @param   string  Field name
     * @param   mixed   Value
     */
    public function addMatch($strFieldName, $varValue)
    {
        $this->arrMatches[$strFieldName][] = $varValue;
    }

    /**
     * Validates a widget
     * @param   mixed Widget value
     * @param   \Widget
     * @param   \Haste\Form\Form
     * @return  mixed Widget value
     */
    public function validate($varValue, $objWidget, $objForm)
    {
        foreach ($this->arrMatches as $strFieldName => $arrValues) {
            $objTarget = $objForm->getWidget($strFieldName);
            // @todo if the target widget isn't validated, we have no value here
            // can we do anything about it?
            $varTargetValue = $objTarget->value;

            if (trim($varValue) == ''
                && $this->arrMatches[$objTarget->name]
                && in_array($varTargetValue, $this->arrMatches[$objTarget->name])
            ) {
                $objWidget->class = 'error';
                $objWidget->addError($GLOBALS['TL_LANG']['MSC']['mandatory']);

                // Don't add this error twice
                break;
            }
        }

        return $varValue;
    }
} 