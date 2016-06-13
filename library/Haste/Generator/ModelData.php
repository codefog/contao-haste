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

namespace Haste\Generator;

use Haste\Haste;
use Haste\Data\Collection;
use Haste\Data\Plain;
use Haste\Data\Relation;
use Haste\Data\Timestamp;
use Haste\Util\Format;


class ModelData
{

    /**
     * Auto-format model data based on DCA config
     * @param   \Model
     * @param   callable
     * @return  \ArrayObject
     */
    public static function generate(\Model $objModel=null, $varCallable=null)
    {
        if (null === $objModel) {
            return new \ArrayObject(array(), \ArrayObject::ARRAY_AS_PROPS);
        }

        $strTable = $objModel->getTable();
        $objDca = new \DcaExtractor($strTable);
        $arrRelations = $objDca->getRelations();
        $arrData = array();

        \System::loadLanguageFile($strTable);
        \Controller::loadDataContainer($strTable);

        $arrFields = &$GLOBALS['TL_DCA'][$strTable]['fields'];

        foreach ($objModel->row() as $strField => $varValue) {

            $arrAdditional = array();
            $strLabel = Format::dcaLabel($strTable, $strField);

            if (isset($arrRelations[$strField])) {

                $objRelated = $objModel->getRelated($strField);

                if ($objRelated == null) {
                    $arrData[$strField] = new Plain('', $strLabel, $arrAdditional);
                } elseif ($objRelated instanceof \Model\Collection) {
                    $arrCollection = array();
                    foreach ($objRelated as $objRelatedModel) {
                        $arrCollection[] = new Relation($objRelatedModel, '', array(), $varCallable);
                    }

                    $arrData[$strField] = new Collection($arrCollection, $strLabel);
                } else {
                    $arrData[$strField] = new Relation($objRelated, $strLabel, array(), $varCallable);
                }

                continue;
            }

            $arrAdditional['formatted'] = Format::dcaValue($strTable, $strField, $varValue);

            if (in_array($arrFields[$strField]['eval']['rgxp'], array('date', 'datim', 'time'))) {
                $arrData[$strField] = new Timestamp($varValue, $strLabel, $arrAdditional);
            } else {
                $arrData[$strField] = new Plain($varValue, $strLabel, $arrAdditional);
            }
        }

        if (null !== $varCallable) {
            call_user_func_array($varCallable, array($objModel, &$arrData));
        }

        return new \ArrayObject($arrData, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Generate tokens for model data
     * @param   \Model
     * @param   callable
     * @return  array
     */
    public static function generateTokens(\Model $objModel=null, $varCallable=null)
    {
        $objData = static::generate($objModel, $varCallable);

        $fnGenerate = function($objData, $strPrefix='') use (&$fnGenerate) {

            $arrTokens = array();

            foreach ($objData as $key => $value) {
                $arrTokens[$strPrefix.$key] = (string) $value;

                if (is_array($value) || $value instanceof \ArrayObject) {
                    $arrTokens = array_merge($arrTokens, $fnGenerate($value, $strPrefix.$key.'_'));
                }
            }

            return $arrTokens;
        };

        return $fnGenerate($objData);
    }
}
