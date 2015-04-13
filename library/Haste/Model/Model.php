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

            /** @type \Model $strClass */
            $strClass = static::getClassFromTable($arrRelation['related_table']);

            if (class_exists($strClass)) {
                $arrIds = \Database::getInstance()->prepare("SELECT " . $arrRelation['related_field'] . " FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['reference_field'] . "=?")
                                                  ->execute($this->$arrRelation['reference'])
                                                  ->fetchEach($arrRelation['related_field']);

                if (empty($arrIds)) {
                    return null;
                }

                $collection = array();

                // Fetch from registry first (only possible if no options and the relation field is the PK)
                if (empty($arrOptions) && $arrRelation['field'] === $strClass::getPk()) {
                    foreach ($arrIds as $k => $id) {
                        $model = \Model\Registry::getInstance()->fetch($arrRelation['related_table'], $id);
                        if ($model !== null) {
                            unset($arrIds[$k]);
                        }

                        $collection[$id] = $model;
                    }
                }

                // Fetch remaining
                if (!empty($arrIds)) {
                    $remainingModels = $strClass::findBy(array($arrRelation['related_table'] . "." . $arrRelation['field'] . " IN('" . implode("','", $arrIds) . "')"), null, $arrOptions);
                    foreach ($remainingModels as $remaining) {
                        $collection[$remaining->{$arrRelation['field']}] = $remaining;
                    }
                }

                $this->arrRelated[$strKey] = new \Model\Collection($collection, $strClass::getTable());
            }
        }

        return parent::getRelated($strKey, $arrOptions);
    }

    /**
     * Get the reference values and return them as array
     * @param string
     * @param string
     * @param mixed
     * @return array
     */
    public static function getReferenceValues($strTable, $strField, $varValue=null)
    {
        $arrRelation = Relations::getRelation($strTable, $strField);

        if ($arrRelation === false) {
            throw new \Exception('Field ' . $strField . ' does not seem to be related!');
        }

        $arrValues = (array) $varValue;

        return \Database::getInstance()->prepare("SELECT " . $arrRelation['reference_field'] . " FROM " . $arrRelation['table'] . (!empty($arrValues) ? (" WHERE " . $arrRelation['related_field'] . " IN ('" . implode("','", $arrValues) . "')") : ""))
                                       ->execute()
                                       ->fetchEach($arrRelation['reference_field']);
    }

    /**
     * Get the related values and return them as array
     *
     * @param string $strTable
     * @param string $strField
     * @param mixed  $varValue
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function getRelatedValues($strTable, $strField, $varValue=null)
    {
        $arrRelation = Relations::getRelation($strTable, $strField);

        if ($arrRelation === false) {
            throw new \Exception('Field ' . $strField . ' does not seem to be related!');
        }

        $arrValues = (array) $varValue;

        return \Database::getInstance()->prepare("SELECT " . $arrRelation['related_field'] . " FROM " . $arrRelation['table'] . (!empty($arrValues) ? (" WHERE " . $arrRelation['reference_field'] . " IN ('" . implode("','", $arrValues) . "')") : ""))
                                       ->execute()
                                       ->fetchEach($arrRelation['related_field']);
    }

    /**
     * Set the related values
     *
     * @param string $strTable
     * @param string $strField
     * @param mixed  $varReference
     * @param mixed  $varValue
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function setRelatedValues($strTable, $strField, $varReference, $varValue)
    {
        $arrRelation = Relations::getRelation($strTable, $strField);

        if ($arrRelation === false) {
            throw new \Exception('Field ' . $strField . ' does not seem to be related!');
        }

        static::deleteRelatedValues($strTable, $strField, $varReference);

        $arrValues = (array) $varValue;

        foreach ($arrValues as $varValue) {
            $arrSet = array(
                $arrRelation['reference_field'] => $varReference,
                $arrRelation['related_field'] => $varValue,
            );

            \Database::getInstance()->prepare("INSERT INTO " . $arrRelation['table'] . " %s")
                ->set($arrSet)
                ->execute();
        }
    }

    /**
     * Delete the related values
     *
     * @param string $strTable
     * @param string $strField
     * @param mixed  $varReference
     *
     * @throws \Exception
     */
    public static function deleteRelatedValues($strTable, $strField, $varReference)
    {
        $arrRelation = Relations::getRelation($strTable, $strField);

        if ($arrRelation === false) {
            throw new \Exception('Field ' . $strField . ' does not seem to be related!');
        }

        \Database::getInstance()->prepare("DELETE FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['reference_field'] . "=?")
            ->execute($varReference);
    }
}
