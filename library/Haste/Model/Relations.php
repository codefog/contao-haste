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
        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $arrField) {
            if (static::getRelatedTable($arrField) != '') {
                $GLOBALS['TL_DCA'][$strTable]['config']['onsubmit_callback'][] = array('Haste\Model\Relations', 'updateRelatedRecords');
                $GLOBALS['TL_DCA'][$strTable]['config']['ondelete_callback'][] = array('Haste\Model\Relations', 'deleteRelatedRecords');
                break;
            }
        }
    }

    /**
     * Update the records in related table
     * @param \DataContainer
     */
    public function updateRelatedRecords(\DataContainer $dc)
    {
        foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'] as $strField => $arrField) {
            $strRelatedTable = static::getRelatedTable($arrField);

            if ($strRelatedTable == '') {
                continue;
            }

            $arrValues = deserialize($dc->activeRecord->$strField, true);
            $this->purgeRelatedRecords($dc->table, $dc->id, $strRelatedTable);

            foreach ($arrValues as $value) {
                $arrSet = array(
                    $dc->table . '_id' => $dc->id,
                    $strRelatedTable . '_id' => $value,
                );

                \Database::getInstance()->prepare("INSERT INTO " . static::getTableName($dc->table, $strRelatedTable) . " %s")
                                        ->set($arrSet)
                                        ->execute();
            }
        }
    }

    /**
     * Delete the records in related table
     * @param \DataContainer
     */
    public function deleteRelatedRecords(\DataContainer $dc)
    {
        foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'] as $strField => $arrField) {
            $strRelatedTable = static::getRelatedTable($arrField);

            if ($strRelatedTable == '') {
                continue;
            }

            $this->purgeRelatedRecords($dc->table, $dc->id, $strRelatedTable);
        }
    }

    /**
     * Purge the related records
     * @param string
     * @param integer
     * @param string
     */
    protected function purgeRelatedRecords($strTable, $intId, $strRelatedTable)
    {
        \Database::getInstance()->prepare("DELETE FROM " . static::getTableName($strTable, $strRelatedTable) . " WHERE " . $strTable . "_id=?")
                                ->execute($intId);
    }

    /**
     * Add the relation tables
     * @param array
     * @return array
     */
    public function addRelationTables($arrQueries)
    {
        $arrTables = \Database::getInstance()->listTables();

        foreach ($GLOBALS['TL_DCA'] as $strTable => $arrDca) {
            foreach ($arrDca['fields'] as $strField => $arrField) {
                $strRelatedTable = static::getRelatedTable($arrField);

                if ($strRelatedTable == '') {
                    continue;
                }

                $strRelationTable = static::getTableName($strTable, $strRelatedTable);

                // The table already exists
                if (in_array($strRelationTable, $arrTables)) {
                    continue;
                }

                $arrQueries['CREATE'][] = "CREATE TABLE `" . $strRelationTable . "` (\n" .
"  `" . $strTable . "_id` int(10) unsigned NOT NULL default '0',\n" .
"  `" . $strRelatedTable . "_id` int(10) unsigned NOT NULL default '0',\n" .
"  KEY `" . $strTable . "_id` (`" . $strTable . "_id`),\n" .
"  KEY `" . $strRelatedTable . "_id` (`" . $strRelatedTable . "_id`)\n" .
") ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            }
        }

        return $arrQueries;
    }

    /**
     * Get the related table name and return it as string
     * @param array
     * @return string
     */
    public static function getRelatedTable($arrConfig)
    {
        if (!isset($arrConfig['relation']) || !isset($arrConfig['relation']['table']) || $arrConfig['relation']['type'] != 'haste-ManyToMany') {
            return '';
        }

        return $arrConfig['relation']['table'];
    }

    /**
     * Get the relations table name in the following format:
     * Parameters: tl_table_one, tl_table_two
     * Returned value: tl_table_one_tl_table_two
     * @param string
     * @param string
     * @return string
     */
    public static function getTableName($strTableOne, $strTableTwo)
    {
        $arrTables = array($strTableOne, $strTableTwo);
        natcasesort($arrTables);
        return implode('_', $arrTables);
    }
}
