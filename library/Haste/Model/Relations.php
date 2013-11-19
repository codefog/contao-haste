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

class Relations extends \Backend
{

    /**
     * Relations cache
     * @var array
     */
    private static $arrRelationsCache;

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
            $GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'][] = array('Haste\Model\Relations', 'copyRelatedRecords');
        }

        $GLOBALS['TL_DCA'][$strTable]['config']['ondelete_callback'][] = array('Haste\Model\Relations', 'cleanRelatedRecords');
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
            $this->purgeRelatedRecords($arrRelation, $dc->$arrRelation['reference']);

            foreach ($arrValues as $value) {
                $arrSet = array(
                    $arrRelation['reference_field'] => $dc->$arrRelation['reference'],
                    $arrRelation['related_field'] => $value,
                );

                $this->Database->prepare("INSERT INTO " . $arrRelation['table'] . " %s")
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

            $this->purgeRelatedRecords($arrRelation, $dc->$arrRelation['reference']);
        }
    }

    /**
     * Copy the records in related table
     * @param integer
     * @param \DataContainer
     */
    public function copyRelatedRecords($intId, \DataContainer $dc)
    {
        foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'] as $strField => $arrField) {
            $arrRelation = static::getRelation($dc->table, $strField);

            if ($arrRelation === false) {
                continue;
            }

            $varReference = $intId;

            // Get the reference value (if not an ID)
            if ($arrRelation['reference'] != 'id') {
                $objReference = $this->Database->prepare("SELECT " . $arrRelation['reference'] . " FROM " . $dc->table . " WHERE id=?")
                                               ->limit(1)
                                               ->execute($intId);

                if ($objReference->numRows) {
                    $varReference = $objReference->$arrRelation['reference'];
                }
            }

            $objValues = $this->Database->prepare("SELECT " . $arrRelation['related_field'] . " FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['reference_field'] . "=?")
                                        ->execute($dc->$arrRelation['reference']);

            while ($objValues->next()) {
                $this->Database->prepare("INSERT INTO " . $arrRelation['table'] . " (`" . $arrRelation['reference_field'] . "`, `" . $arrRelation['related_field'] . "`) VALUES (?, ?)")
                               ->execute($varReference, $objValues->$arrRelation['related_field']);
            }
        }
    }

    /**
     * Clean the records in related table
     * @param \DataContainer
     */
    public function cleanRelatedRecords(\DataContainer $dc)
    {
        // Only check the active modules
		foreach ($this->Config->getActiveModules() as $strModule) {
			$strDir = 'system/modules/' . $strModule . '/dca';

			if (!is_dir(TL_ROOT . '/' . $strDir)) {
				continue;
			}

			foreach (scan(TL_ROOT . '/' . $strDir) as $strFile) {
				if (substr($strFile, -4) != '.php') {
					continue;
				}

                $this->loadDataContainer(substr($strFile, 0, -4));
			}
		}

        foreach ($GLOBALS['TL_DCA'] as $strTable => $arrTable) {
            foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
                $arrRelation = static::getRelation($strTable, $strField);

                if ($arrRelation === false || $arrRelation['related_table'] != $dc->table) {
                    continue;
                }

                $this->Database->prepare("DELETE FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['related_field'] . "=?")
                               ->execute($dc->$arrRelation['field']);
            }
        }
    }

    /**
     * Delete the related records on table revision
     * @param string
     * @param array
     * @return boolean
     */
    public function reviseRelatedRecords($strTable, $arrIds)
    {
        if (empty($arrIds)) {
            return false;
        }

        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
            $arrRelation = static::getRelation($strTable, $strField);

            if ($arrRelation === false) {
                continue;
            }

            $objDelete = $this->Database->execute("SELECT " . $arrRelation['reference'] . " FROM " . $strTable . " WHERE id IN (" . implode(',', array_map('intval', $arrIds)) . ") AND tstamp=0");

            while ($objDelete->next()) {
                $this->purgeRelatedRecords($arrRelation, $objDelete->$arrRelation['reference']);
            }
        }

        return false;
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
            $varValue = Model::getRelatedValues($dc->table, $dc->field,$dc->$arrRelation['reference']);
        }

        return $varValue;
    }

    /**
     * Purge the related records
     * @param array
     * @param mixed
     */
    protected function purgeRelatedRecords($arrRelation, $varId)
    {
        $this->Database->prepare("DELETE FROM " . $arrRelation['table'] . " WHERE " . $arrRelation['reference_field'] . "=?")
                       ->execute($varId);
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
        if (!isset($GLOBALS['TL_DCA'][$strTable])) {
            $objSelf = new self();
            $objSelf->loadDataContainer($strTable);
        }

        $strCacheKey = $strTable . '_' . $strField;

        if (!isset(static::$arrRelationsCache[$strCacheKey])) {
            $varRelation = false;
            $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['relation'];

            if (is_array($arrField) && isset($arrField['table']) && $arrField['type'] == 'haste-ManyToMany') {
                $varRelation = array();

                // The relations table
                $varRelation['table'] = isset($arrField['relationTable']) ? $arrField['relationTable'] : static::getTableName($strTable, $arrField['table']);

                // The related field
                $varRelation['reference'] = isset($arrField['reference']) ? $arrField['reference'] : 'id';
                $varRelation['field'] = isset($arrField['field']) ? $arrField['field'] : 'id';

                // Current table data
                $varRelation['reference_table'] = $strTable;
                $varRelation['reference_field'] = str_replace('tl_', '', $strTable) . '_' . $varRelation['reference'];
                $varRelation['reference_sql'] = isset($arrField['referenceSql']) ? $arrField['referenceSql'] : "int(10) unsigned NOT NULL default '0'";

                // Related table data
                $varRelation['related_table'] = $arrField['table'];
                $varRelation['related_field'] = str_replace('tl_', '', $arrField['table']) . '_' . $varRelation['field'];
                $varRelation['related_sql'] = isset($arrField['fieldSql']) ? $arrField['fieldSql'] : "int(10) unsigned NOT NULL default '0'";
            }

            static::$arrRelationsCache[$strCacheKey] = $varRelation;
        }

        return static::$arrRelationsCache[$strCacheKey];
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
