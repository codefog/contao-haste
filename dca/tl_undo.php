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

/**
 * Replace the "undo" button href
 */
$GLOBALS['TL_DCA']['tl_undo']['list']['operations']['undo']['button_callback'] = array('Haste\Util\Undo', 'button');

/**
 * Add fields to tl_undo
 */
$GLOBALS['TL_DCA']['tl_undo']['fields']['haste_data'] = array
(
    'sql' => "blob NULL"
);
