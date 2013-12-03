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
     * @param   mixed Widget value
     * @param   \Widget
     * @param   \Haste\Form\Form
     * @return  mixed Widget value
     */
    public function validate($varValue, $objWidget, $objForm);
} 