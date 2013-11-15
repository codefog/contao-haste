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

class Relations
{

    /**
     * Add the relation callbacks to DCA
     * @param string
     */
    public function addRelationCallbacks($strTable)
    {
        $blnCallbacks = false;

        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
            if (static::getRelation($strTable, $strField) !== false) {
                $blnCallbacks = true;

                // Update the field configuration
                $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['doNotSaveEmpty'] = true;
                $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['load_callback'][] = array('Haste\Model\Relations', 'getRelatedRecords');
                $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['save_callback'][] = array('Haste\Model\Relations', 'updateRelatedRecords');
            }
        }

        if ($blnCallbacks) {
            $GLOBALS['TL_DCA'][$strTable]['config']['ondelete_callback'][] = array('Haste\Model\Relations', 'deleteRelatedRecords');
        }
    }

    /**
     * Update the records in related table
     * @param mixed
     * @param \DataContainer
     * @return mixed
     */
    public function updateRelatedRecords($varValue, \DataContainer $dc)
    {
        $arrRelation = static::getRelation($dc->table, $dc->field);

        if ($arrRelation !== false) {
            $arrValues = deserialize($varValue, true);
            $this->purgeRelatedRecords($arrRelation, $dc->id);

            foreach ($arrValues as $value) {
                $arrSet = array(
                    $arrRelation['reference_field'] => $dc->id,
                    $arrRelation['related_field'] => $value,
                );

                \Database::getInstance()->prepare("INSERT INTO " . $arrRelation['table'] . " %s")
                                        ->set($arrSet)
                                        ->execute();
            }
        }

        return null;
    }

    /**
     * Delete the records in related table
     * @param \DataContainer
     */
    public function deleteRelatedRecords(\DataContainer $dc)
    {
        foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'] as $strField => $arrField) {
            $arrRelation = static::getRelation($dc->table, $strField);

            if ($arrRelation === false) {
                continue;
            }

            $this->purgeRelatedRecords($arrRelation, $dc->id);
        }
    }

    /**
     * Get related records of particular field
     * @param mixed
     * @param \DataContainer
     * @return mixed
     */
    public function getRelatedRecords($varValue, \DataContainer $dc)
    {
        $arrRelation = static::getRelation($dc->table, $dc->field);

        if ($arrRelation !== false) {
            $varValue = array();
            $objValues = \Database::getInstance()->prepare("SELECT " . $arrRelation['related_field'] . " FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['reference_field'] . "=?")
                                                 ->execute($dc->id);

            while ($objValues->next()) {
                $varValue[] = $objValues->$arrRelation['related_field'];
            }
        }

        return $varValue;
    }

    /**
     * Purge the related records
     * @param array
     * @param integer
     */
    protected function purgeRelatedRecords($arrRelation, $intId)
    {
        \Database::getInstance()->prepare("DELETE FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['reference_field'] . "=?")
                                ->execute($intId);
    }

    /**
     * Add the relation tables
     * @param array
     * @return array
     */
    public function addRelationTables($arrTables)
    {
        foreach ($GLOBALS['TL_DCA'] as $strTable => $arrDca) {
            foreach ($arrDca['fields'] as $strField => $arrField) {
                $arrRelation = static::getRelation($strTable, $strField);

                if ($arrRelation === false) {
                    continue;
                }

                $arrTables[$arrRelation['table']]['TABLE_FIELDS'][$arrRelation['reference_field']] = "`" . $arrRelation['reference_field'] . "` " . $arrRelation['reference_sql'];
                $arrTables[$arrRelation['table']]['TABLE_FIELDS'][$arrRelation['related_field']] = "`" . $arrRelation['related_field'] . "` " . $arrRelation['related_sql'];
                $arrTables[$arrRelation['table']]['TABLE_CREATE_DEFINITIONS'][$arrRelation['reference_field']] = "KEY `" . $arrRelation['reference_field'] . "` (`" . $arrRelation['reference_field'] . "`)";
                $arrTables[$arrRelation['table']]['TABLE_CREATE_DEFINITIONS'][$arrRelation['related_field']] = "KEY `" . $arrRelation['related_field'] . "` (`" . $arrRelation['related_field'] . "`)";
                $arrTables[$arrRelation['table']]['TABLE_OPTIONS'] = ' ENGINE=MyISAM  DEFAULT CHARSET=utf8';
            }
        }

        return $arrTables;
    }

    /**
     * Get the relation of particular field
     * or false if there is no relation
     * @param string
     * @param string
     * @return array|boolean
     */
    public static function getRelation($strTable, $strField)
    {
        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['relation'];

        if (!is_array($arrField) || !isset($arrField['table']) || $arrField['type'] != 'haste-ManyToMany') {
            return false;
        }

        $arrConfig = array();

        // The relations table
        $arrConfig['table'] = isset($arrField['relationTable']) ? $arrField['relationTable'] : static::getTableName($strTable, $arrField['table']);

        // The related field
        $arrConfig['field'] = isset($arrField['field']) ? $arrField['field'] : 'id';

        // Current table data
        $arrConfig['reference_table'] = $strTable;
        $arrConfig['reference_field'] = str_replace('tl_', '', $strTable) . '_' . (isset($arrField['reference']) ? $arrField['reference'] : 'id');
        $arrConfig['reference_sql'] = isset($arrField['referenceSql']) ? $arrField['referenceSql'] : "int(10) unsigned NOT NULL default '0'";

        // Related table data
        $arrConfig['related_table'] = $arrField['table'];
        $arrConfig['related_field'] = str_replace('tl_', '', $arrField['table']) . '_' . $arrConfig['field'];
        $arrConfig['related_sql'] = isset($arrField['fieldSql']) ? $arrField['fieldSql'] : "int(10) unsigned NOT NULL default '0'";

        return $arrConfig;
    }

    /**
     * Get the relations table name in the following format:
     * Parameters: tl_table_one, tl_table_two
     * Returned value: tl_table_one_table_two
     * @param string
     * @param string
     * @return string
     */
    public static function getTableName($strTableOne, $strTableTwo)
    {
        $arrTables = array($strTableOne, $strTableTwo);
        natcasesort($arrTables);
        return $arrTables[0] . '_' . str_replace('tl_', '', $arrTables[1]);
    }
}
