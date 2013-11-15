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
        $arrRelation = Relations::getRelation(static::$strTable, $strKey);

        if ($arrRelation !== false) {
            $strClass = static::getClassFromTable($arrRelation['related_table']);

            if (class_exists($strClass)) {
                $arrIds = \Database::getInstance()->prepare("SELECT " . $arrRelation['related_field'] . " FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['reference_field'] . "=?")
                                                  ->execute($this->$arrRelation['reference'])
                                                  ->fetchEach($arrRelation['related_field']);

                if (empty($arrIds)) {
                    return null;
                }

                $objModel = $strClass::findBy(array($arrRelation['field'] . " IN('" . implode("','", $arrIds) . "')"), null, $arrOptions);
                $this->arrRelated[$strKey] = $objModel;
            }
        }

        return parent::getRelated($strKey, $arrOptions);
    }
}
