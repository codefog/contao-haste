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
    private static $arrRelationsCache = array();

    /**
     * Filterable fields
     * @var array
     */
    private static $arrFilterableFields = array();

    /**
     * Add the relation callbacks to DCA
     * @param string
     */
    public function addRelationCallbacks($strTable)
    {
        if (!isset($GLOBALS['TL_DCA'][$strTable]['fields'])) {
            return;
        }

        $blnCallbacks = false;

        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
            $arrRelation = static::getRelation($strTable, $strField);

            if ($arrRelation === false) {
                continue;
            }

            $blnCallbacks = true;

            // Update the field configuration
            $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['doNotSaveEmpty'] = true;
            $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['load_callback'][] = array('Haste\Model\Relations', 'getRelatedRecords');
            $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['save_callback'][] = array('Haste\Model\Relations', 'updateRelatedRecords');

            // Use custom filtering
            if ($arrField['filter']) {
                $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['filter'] = false;
                static::$arrFilterableFields[$strField] = $arrRelation;
            }
        }

        // Add global callbacks
        if ($blnCallbacks) {
            $GLOBALS['TL_DCA'][$strTable]['config']['ondelete_callback'][] = array('Haste\Model\Relations', 'deleteRelatedRecords');
            $GLOBALS['TL_DCA'][$strTable]['config']['oncopy_callback'][] = array('Haste\Model\Relations', 'copyRelatedRecords');
        }

        $GLOBALS['TL_DCA'][$strTable]['config']['ondelete_callback'][] = array('Haste\Model\Relations', 'cleanRelatedRecords');

        // Add filter callbacks
        if (!empty(static::$arrFilterableFields)) {
            $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'][] = array('Haste\Model\Relations', 'filterByRelations');
            $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['panelLayout'] = str_replace('filter', 'haste_filter;filter', $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['panelLayout']);
            $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['panel_callback']['haste_filter'] = array('Haste\Model\Relations', 'addRelationFilters');
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
        static $blnPurged;
        $arrRelation = static::getRelation($dc->table, $dc->field);

        if ($arrRelation !== false) {
            $arrValues = deserialize($varValue, true);

            if (!$blnPurged) {
                $this->purgeRelatedRecords($arrRelation, $dc->$arrRelation['reference']);
                $blnPurged = true;
            }

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
        if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'])) {
            return;
        }

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
        if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'])) {
            return;
        }

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
     * @throws \RuntimeException
     */
    public function cleanRelatedRecords()
    {
        $dc = null;

        // Try to find the \DataContainer instance (see #37)
        foreach (func_get_args() as $arg) {
            if ($arg instanceof \DataContainer) {
                $dc = $arg;
                break;
            }
        }

        if ($dc === null) {
            throw new \RuntimeException('Sorry but there seems to be no valid DataContainer instance!');
        }

        // Only check the active modules
        foreach (\ModuleLoader::getActive() as $strModule) {
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
            if (!isset($GLOBALS['TL_DCA'][$strTable]['fields'])) {
                continue;
            }

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
        if (empty($arrIds) || !isset($GLOBALS['TL_DCA'][$strTable]['fields'])) {
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
            if (!isset($GLOBALS['TL_DCA'][$strTable]['fields'])) {
                continue;
            }

            foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
                $arrRelation = static::getRelation($strTable, $strField);

                if ($arrRelation === false) {
                    continue;
                }

                $arrTables[$arrRelation['table']]['TABLE_FIELDS'][$arrRelation['reference_field']] = "`" . $arrRelation['reference_field'] . "` " . $arrRelation['reference_sql'];
                $arrTables[$arrRelation['table']]['TABLE_FIELDS'][$arrRelation['related_field']] = "`" . $arrRelation['related_field'] . "` " . $arrRelation['related_sql'];
                $arrTables[$arrRelation['table']]['TABLE_CREATE_DEFINITIONS'][$arrRelation['reference_field'] . "_" . $arrRelation['related_field']] = "UNIQUE KEY `" . $arrRelation['reference_field'] . "_" . $arrRelation['related_field'] . "` (`" . $arrRelation['reference_field'] . "`, `" . $arrRelation['related_field'] . "`)";
                $arrTables[$arrRelation['table']]['TABLE_OPTIONS'] = ' ENGINE=MyISAM  DEFAULT CHARSET=utf8';
            }
        }

        return $arrTables;
    }

    /**
     * Filter records by relations set in custom filter
     * @param \DataContainer
     */
    public function filterByRelations(\DataContainer $dc)
    {
        if (empty(static::$arrFilterableFields)) {
            return;
        }

        $arrIds = array();
        $blnFilter = false;
        $session = $this->Session->getData();

        foreach (array_keys(static::$arrFilterableFields) as $field) {
            if (isset($session['filter'][$dc->table][$field])) {
                $blnFilter = true;
                $ids = Model::getReferenceValues($dc->table, $field, $session['filter'][$dc->table][$field]);
                $arrIds = empty($arrIds) ? $ids : array_intersect($arrIds, $ids);
            }
        }

        if ($blnFilter) {
            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = empty($arrIds) ? array(0) : array_unique($arrIds);
        }
    }

    /**
     * Add the relation filters
     * @param \DataContainer
     * @return string
     */
    public function addRelationFilters(\DataContainer $dc)
    {
        if (empty(static::$arrFilterableFields)) {
            return '';
        }

        $filter = ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] == 4) ? $dc->table.'_'.CURRENT_ID : $dc->table;
        $session = $this->Session->getData();

        // Set filter from user input
        if (\Input::post('FORM_SUBMIT') == 'tl_filters') {
            foreach (array_keys(static::$arrFilterableFields) as $field) {
                if (\Input::post($field, true) != 'tl_' . $field) {
                    $session['filter'][$filter][$field] = \Input::post($field, true);
                } else {
                    unset($session['filter'][$filter][$field]);
                }
            }

            $this->Session->setData($session);
        }

        $return .= '<div class="tl_filter tl_subpanel">
<strong>' . $GLOBALS['TL_LANG']['HST']['advanced_filter'] . '</strong> ';

        foreach (static::$arrFilterableFields as $field => $arrRelation) {
            $return .= '<select name="' . $field . '" class="tl_select' . (isset($session['filter'][$filter][$field]) ? ' active' : '') . '">
    <option value="tl_' . $field . '">' . $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label'][0] . '</option>
    <option value="tl_' . $field . '">---</option>';

            $arrIds = Model::getRelatedValues($arrRelation['reference_table'], $field);

            if (empty($arrIds)) {
                $return .= '</select> ';
                continue;
            }

            $options = array_unique($arrIds);
            $options_callback = array();

            // Call the options_callback
            if ((is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback']) || is_callable($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'])) && !$GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference']) {
                if (is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'])) {
                    $strClass = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'][0];
                    $strMethod = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'][1];

                    $this->import($strClass);
                    $options_callback = $this->$strClass->$strMethod($this);
                } elseif (is_callable($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'])) {
                    $options_callback = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback']($this);
                }

                // Sort options according to the keys of the callback array
                $options = array_intersect(array_keys($options_callback), $options);
            }

            $options_sorter = array();

            // Options
            foreach ($options as $kk=>$vv) {
                $value = $vv;

                // Options callback
                if (!empty($options_callback) && is_array($options_callback)) {
                    $vv = $options_callback[$vv];
                } elseif (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['foreignKey'])) {
                    // Replace the ID with the foreign key
                    $key = explode('.', $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['foreignKey'], 2);

                    $objParent = $this->Database->prepare("SELECT " . $key[1] . " AS value FROM " . $key[0] . " WHERE id=?")
                                                ->limit(1)
                                                ->execute($vv);

                    if ($objParent->numRows) {
                        $vv = $objParent->value;
                    }
                }

                $option_label = '';

                // Use reference array
                if (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'])) {
                    $option_label = is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'][$vv]) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'][$vv][0] : $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'][$vv];
                } elseif ($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options'])) {
                    // Associative array
                    $option_label = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options'][$vv];
                }

                // No empty options allowed
                if (!strlen($option_label)) {
                    $option_label = $vv ?: '-';
                }

                $options_sorter['  <option value="' . specialchars($value) . '"' . ((isset($session['filter'][$filter][$field]) && $value == $session['filter'][$filter][$field]) ? ' selected="selected"' : '').'>'.$option_label.'</option>'] = utf8_romanize($option_label);
            }

            $return .= "\n" . implode("\n", array_keys($options_sorter));
            $return .= '</select> ';
        }

        return $return . '</div>';
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
            \Haste\Haste::getInstance()->call('loadDataContainer', $strTable);
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
