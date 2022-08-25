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
 * Backend widgets
 */
$GLOBALS['BE_FFL']['numberField'] = 'Number\BackendWidget';


/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions'][] = array('Dca\AjaxOperations', 'executePostActions');
$GLOBALS['TL_HOOKS']['getContentElement'][]  = ['EventListener\AjaxReloadListener', 'onGetContentElement'];
$GLOBALS['TL_HOOKS']['getFrontendModule'][]  = ['EventListener\AjaxReloadListener', 'onGetFrontendModule'];
$GLOBALS['TL_HOOKS']['loadDataContainer'][]  = array('Dca\DateRangeFilter', 'addCallbacks');
$GLOBALS['TL_HOOKS']['loadDataContainer'][]  = array('Dca\AjaxOperations', 'modifyDca');
$GLOBALS['TL_HOOKS']['loadDataContainer'][]  = array('Model\Relations', 'addRelationCallbacks');
$GLOBALS['TL_HOOKS']['modifyFrontendPage'][] = ['EventListener\AjaxReloadListener', 'onModifyFrontendPage'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][]  = array('Util\InsertTag', 'replaceHasteInsertTags');
$GLOBALS['TL_HOOKS']['reviseTable'][]        = array('Model\Relations', 'reviseRelatedRecords');

if (TL_MODE !== 'FE') {
    $GLOBALS['TL_HOOKS']['sqlGetFromFile'][] = ['Model\Relations', 'addRelationTables'];
}


/**
 * Haste hooks
 */
$GLOBALS['HASTE_HOOKS']['undoData'] = array
(
    array('Model\Relations', 'undoRelations')
);
