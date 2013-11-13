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

namespace Haste\Model;

abstract class Model extends \Model
{

    /**
     * {@inheritdoc}
     */
    public function getRelated($strKey, array $arrOptions=array())
    {
        $strRelatedTable = Relations::getRelatedTable($GLOBALS['TL_DCA'][static::$strTable]['fields'][$strKey]);

        if ($strRelatedTable != '') {
            $strClass = static::getClassFromTable($strRelatedTable);

            if (class_exists($strClass)) {
                $arrIds = \Database::getInstance()->prepare("SELECT " . $strRelatedTable . "_id FROM " . Relations::getTableName(static::$strTable, $strRelatedTable) . " WHERE " . static::$strTable . "_id=?")
                                                  ->execute($this->id)
                                                  ->fetchEach($strRelatedTable . '_id');

                if (empty($arrIds)) {
                    return null;
                }

                $objModel = $strClass::findBy(array("id IN(" . implode(",", array_map('intval', $arrIds)) . ")"), null, $arrOptions);
                $this->arrRelated[$strKey] = $objModel;
            }
        }

        return parent::getRelated($strKey, $arrOptions);
    }
}
