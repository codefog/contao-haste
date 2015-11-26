<?php

namespace Haste\Form\Validator;


use Haste\Form\Form;

class MandatoryOn implements ValidatorInterface
{
    /**
     * Matches
     * @var array
     */
    protected $arrMatches = array();


    /**
     * Add match
     *
     * @param string $strFieldName
     * @param mixed  $varValue
     */
    public function addMatch($strFieldName, $varValue)
    {
        $this->arrMatches[$strFieldName][] = $varValue;
    }

    /**
     * Validates a widget
     *
     * @param mixed   $varValue Widget value
     * @param \Widget $objWidget
     * @param Form    $objForm
     *
     * @return mixed Widget value
     *
     * @throws \Exception If value is not provided
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
                throw new \Exception($GLOBALS['TL_LANG']['MSC']['mandatory']);
            }
        }

        return $varValue;
    }
} 