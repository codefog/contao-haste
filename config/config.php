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
 * Backend widgets
 */
$GLOBALS['BE_FFL']['numberField'] = 'Haste\Number\BackendWidget';


/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Haste\Model\Relations', 'addRelationCallbacks');
$GLOBALS['TL_HOOKS']['reviseTable'][]       = array('Haste\Model\Relations', 'reviseRelatedRecords');
$GLOBALS['TL_HOOKS']['sqlGetFromFile'][]    = array('Haste\Model\Relations', 'addRelationTables');
