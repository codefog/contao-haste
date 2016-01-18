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

namespace Haste\Util;

class Undo
{

    /**
     * Add additional data to the tl_undo table record
     * @param integer
     * @param string
     * @param mixed
     * @return boolean
     */
    public static function add($intUndoId, $strKey, $varData)
    {
        $objRecords = \Database::getInstance()->prepare("SELECT haste_data FROM tl_undo WHERE id=?")
                                              ->limit(1)
                                              ->execute($intUndoId);

        if (!$objRecords->numRows) {
            return false;
        }

        $arrData = json_decode($objRecords->haste_data, true);
        $arrData[$strKey] = $varData;

        $intAffectedRows = \Database::getInstance()->prepare("UPDATE tl_undo SET haste_data=? WHERE id=?")
                                                   ->execute(json_encode($arrData), $intUndoId)
                                                   ->affectedRows;

        return $intAffectedRows ? true : false;
    }

    /**
     * Undo the record
     * @param integer
     * @return boolean
     */
    public static function undo($intUndoId)
    {
        if (!is_array($GLOBALS['HASTE_HOOKS']['undoData']) || empty($GLOBALS['HASTE_HOOKS']['undoData']) || !static::hasData($intUndoId)) {
            return false;
        }

        $objRecords = \Database::getInstance()->prepare("SELECT * FROM tl_undo WHERE id=?")
                                              ->limit(1)
                                              ->execute($intUndoId);

        $error = false;
        $query = $objRecords->query;
        $data = deserialize($objRecords->data);

        if (!is_array($data)) {
            return false;
        }

        $arrFields = array();
        $hasteData = json_decode($objRecords->haste_data, true);

        // Restore the data
        foreach ($data as $table => $fields) {

            // Get the currently available fields
            if (!isset($arrFields[$table])) {
                $arrFields[$table] = array_flip(\Database::getInstance()->getFieldnames($table));
            }

            foreach ($fields as $row) {

                // Unset fields that no longer exist in the database
                $row = array_intersect_key($row, $arrFields[$table]);

                // Re-insert the data
                $objInsertStmt = \Database::getInstance()->prepare("INSERT INTO " . $table . " %s")
                                                         ->set($row)
                                                         ->execute();

                // Do not delete record from tl_undo if there is an error
                if ($objInsertStmt->affectedRows < 1) {
                    $error = true;
                    continue;
                }

                $insertId = $objInsertStmt->insertId;

                foreach ($GLOBALS['HASTE_HOOKS']['undoData'] as $callback) {
                    if (is_array($callback)) {
                        $objClass = new $callback[0]();
                        $objClass->{$callback[1]}($hasteData, $insertId, $table, $row);
                    } elseif (is_callable($callback)) {
                        $callback($hasteData, $insertId, $table, $row);
                    }
                }
            }
        }

        // Add log entry and delete record from tl_undo if there was no error
        if (!$error) {
            \System::log('Undone '. $query, __METHOD__, TL_GENERAL);

            \Database::getInstance()->prepare("DELETE FROM tl_undo WHERE id=?")
                                    ->limit(1)
                                    ->execute($intUndoId);
        }

        return !$error;
    }

    /**
     * Check if the record has data to undo
     * @param integer
     * @return boolean
     */
    public static function hasData($intUndoId)
    {
        $objRecords = \Database::getInstance()->prepare("SELECT haste_data FROM tl_undo WHERE id=?")
                                              ->limit(1)
                                              ->execute($intUndoId);

        if (!$objRecords->numRows) {
            return false;
        }

        $arrData = json_decode($objRecords->haste_data, true);

        return !empty($arrData);
    }

    /**
     * Undo the record manually triggered in the backend
     * @param \DataContainer
     */
    public function callback(\DataContainer $dc)
    {
        static::undo($dc->id);
        \System::redirect(\System::getReferer());
    }

    /**
     * Return the "undo" button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function button($row, $href, $label, $title, $icon, $attributes)
    {
        if (static::hasData($row['id'])) {
            $href = '&amp;key=haste_undo';
        }

        return '<a href="'.\Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
    }
}
