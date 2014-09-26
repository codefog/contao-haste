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

namespace Haste\Form\Validator;


interface ValidatorInterface
{
    /**
     * Validates a widget
     *
     * @param mixed            $varValue Widget value
     * @param \Widget          $objWidget
     * @param \Haste\Form\Form $objForm
     *
     * @return mixed Widget value
     * @todo Add type hinting to the method parameters (in 5.0, as it would break the Interface)
     */
    public function validate($varValue, $objWidget, $objForm);
} 