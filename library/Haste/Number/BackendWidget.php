<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2013 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Number;

/**
 * Class BackendWidget
 * Provide methods to handle Number input.
 */
class BackendWidget extends \TextField
{

    /**
     * Make sure we have the correct value
     * @param string
     * @param mixed
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey)
        {
            case 'value':
                $this->varValue = Number::create($varValue);
            break;
        }

        parent::__set($strKey, $varValue);
    }


    /**
     * Validate input and set value
     * @param mixed
     * @return mixed
     */
    public function validator($varInput)
    {
        try {
            $varInput = Number::create($varInput)->getAmount();
        } catch(\InvalidArgumentException $e) {
            $this->addError($GLOBALS['TL_LANG']['ERR']['numberInputNotAllowed']);
        }

        return parent::validator($varInput);
    }
}
