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
 * Add the "haste_undo" operation to "undo" module
 */
$GLOBALS['BE_MOD']['system']['undo']['haste_undo'] = array('Util\Undo', 'callback');


/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][]  = array('Util\InsertTag', 'replaceHasteInsertTags');


// Haste hooks
$GLOBALS['HASTE_HOOKS']['undoData'] = [
    [\Codefog\HasteBundle\DcaRelations::class, 'undoRelations'],
];
