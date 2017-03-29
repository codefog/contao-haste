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

use Haste\Haste;

class Format
{

    /**
     * Format date according to the system config
     * @param   int
     * @return  string
     */
    public static function date($intTstamp)
    {
        $strFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->dateFormat : $GLOBALS['TL_CONFIG']['dateFormat'];

        return \System::parseDate($strFormat, $intTstamp);
    }


    /**
     * Format time according to the system config
     * @param   int
     * @return  string
     */
    public static function time($intTstamp)
    {
        $strFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->timeFormat : $GLOBALS['TL_CONFIG']['timeFormat'];

        return \System::parseDate($strFormat, $intTstamp);
    }


    /**
     * Format date & time according to the system config
     * @param   int
     * @return  string
     */
    public static function datim($intTstamp)
    {
        $strFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->datimFormat : $GLOBALS['TL_CONFIG']['datimFormat'];

        return \System::parseDate($strFormat, $intTstamp);
    }

    /**
     * Get field label based on DCA config
     *
     * @param string $strTable
     * @param string $strField
     *
     * @return string
     */
    public static function dcaLabel($strTable, $strField)
    {
        \System::loadLanguageFile($strTable);
        \Controller::loadDataContainer($strTable);
        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        // Add the "name" key (backwards compatibility)
        if (!isset($arrField['name'])) {
            $arrField['name'] = $strField;
        }

        return static::dcaLabelFromArray($arrField);
    }

    /**
     * Get field label based on field config
     *
     * @param array $arrField
     *
     * @return string
     */
    public static function dcaLabelFromArray(array $arrField)
    {
        if (!empty($arrField['label'])) {
            $strLabel = is_array($arrField['label']) ? $arrField['label'][0] : $arrField['label'];
        } else {
            $strLabel = is_array($GLOBALS['TL_LANG']['MSC'][$arrField['name']]) ? $GLOBALS['TL_LANG']['MSC'][$arrField['name']][0] : $GLOBALS['TL_LANG']['MSC'][$arrField['name']];
        }

        if ($strLabel == '') {
            $strLabel = $arrField['name'];
        }

        return $strLabel;
    }

    /**
     * Format DCA field value according to Contao Core standard
     *
     * @param string              $strTable
     * @param string              $strField
     * @param mixed               $varValue
     * @param \DataContainer|null $objDc
     *
     * @return mixed
     */
    public static function dcaValue($strTable, $strField, $varValue, \DataContainer $objDc = null)
    {
        \System::loadLanguageFile($strTable);
        \Controller::loadDataContainer($strTable);
        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        // Add the "name" key (backwards compatibility)
        if (!isset($arrField['name'])) {
            $arrField['name'] = $strField;
        }

        return static::dcaValueFromArray($arrField, $varValue, $objDc);
    }

    /**
     * Format field value according to Contao Core standard
     *
     * @param array               $arrField
     * @param                     $varValue
     * @param \DataContainer|null $objDc
     *
     * @return mixed
     */
    public static function dcaValueFromArray(array $arrField, $varValue, \DataContainer $objDc = null)
    {
        $varValue = deserialize($varValue);

        if (is_array($arrField['options_callback']) && $objDc !== null) { // Options callback (array)

            $arrCallback = $arrField['options_callback'];
            $arrField['options'] = \System::importStatic($arrCallback[0])->{$arrCallback[1]}($objDc);

        } elseif (is_callable($arrField['options_callback']) && $objDc !== null) { // Options callback (callable)
            $arrField['options'] = $arrField['options_callback']($objDc);

        } elseif (isset($arrField['foreignKey']) && $varValue) { // foreignKey
            $chunks = explode('.', $arrField['foreignKey'], 2);
            $objOptions = \Database::getInstance()->query("SELECT id, " . $chunks[1] . " AS value FROM " . $chunks[0] . " WHERE id IN (" . implode(',', array_map('intval', (array) $varValue)) . ")");
            $arrField['options'] = array();

            while ($objOptions->next()) {
                $arrField['options'][$objOptions->id] = $objOptions->value;
            }
        }

        if (is_array($varValue)) {
            foreach ($varValue as $kk => $vv) {
                $varValue[$kk] = static::dcaValueFromArray($arrField, $vv);
            }

            return implode(', ', $varValue);

        } elseif ($arrField['eval']['rgxp'] == 'date') {
            return static::date($varValue);

        } elseif ($arrField['eval']['rgxp'] == 'time') {
            return static::time($varValue);

        } elseif ($arrField['eval']['rgxp'] == 'datim' || in_array($arrField['flag'], array(5, 6, 7, 8, 9, 10)) || $arrField['name'] == 'tstamp') {
            return static::datim($varValue);

        } elseif ($arrField['inputType'] == 'checkbox' && !$arrField['eval']['multiple']) {
            return strlen($varValue) ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];

        } elseif ($arrField['inputType'] == 'textarea' && ($arrField['eval']['allowHtml'] || $arrField['eval']['preserveTags'])) {
            return specialchars($varValue);

        } elseif (is_array($arrField['reference'])) {
            return isset($arrField['reference'][$varValue]) ? ((is_array($arrField['reference'][$varValue])) ? $arrField['reference'][$varValue][0] : $arrField['reference'][$varValue]) : $varValue;

        } elseif ($arrField['eval']['isAssociative'] || array_is_assoc($arrField['options'])) {
            return isset($arrField['options'][$varValue]) ? ((is_array($arrField['options'][$varValue])) ? $arrField['options'][$varValue][0] : $arrField['options'][$varValue]) : $varValue;
        }

        return $varValue;
    }

    /**
     * @deprecated Deprecated since Haste 4.8, to be removed in Haste 5.
     *             Use RepositoryVersion::format() instead.
     */
    public static function repositoryVersion($aVersion)
    {
        return RepositoryVersion::format($aVersion);
    }

    /**
     * @deprecated Deprecated since Haste 4.8, to be removed in Haste 5.
     *             Use RepositoryVersion::formatShort() instead.
     */
    public static function repositoryShortVersion($aVersion)
    {
        return RepositoryVersion::formatShort($aVersion);
    }
}
