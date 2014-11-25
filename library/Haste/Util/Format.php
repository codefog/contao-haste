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
        Haste::getInstance()->call('loadDataContainer', $strTable);
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
     * @param string $strTable
     * @param string $strField
     * @param mixed $varValue
     *
     * @return mixed
     */
    public static function dcaValue($strTable, $strField, $varValue)
    {
        \System::loadLanguageFile($strTable);
        Haste::getInstance()->call('loadDataContainer', $strTable);
        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        // Add the "name" key (backwards compatibility)
        if (!isset($arrField['name'])) {
            $arrField['name'] = $strField;
        }

        return static::dcaValueFromArray($arrField, $varValue);
    }

    /**
     * Format field value according to Contao Core standard
     *
     * @param array $arrField
     * @param       $varValue
     *
     * @return mixed
     */
    public static function dcaValueFromArray(array $arrField, $varValue)
    {
        $varValue = deserialize($varValue);

        // Get field value
        if (strlen($arrField['foreignKey'])) {
            $chunks = explode('.', $arrField['foreignKey']);
            $varValue = empty($varValue) ? array(0) : $varValue;
            $objKey = \Database::getInstance()->execute("SELECT " . $chunks[1] . " AS value FROM " . $chunks[0] . " WHERE id IN (" . implode(',', array_map('intval', (array) $varValue)) . ")");

            return implode(', ', $objKey->fetchEach('value'));

        } elseif (is_array($varValue)) {
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
     * Format a version number to human readable with long status text
     *
     * Example:
     * <code>
     * echo Format::version(10030042);
     * // will output: 1.3.4 alpha3
     * </code>
     *
     * @param int $aVersion The encoded version
     *
     * @return string The version in human readable format
     */
    public static function repositoryVersion($aVersion)
    {
        $mStatusName = array(
            'alpha1', 'alpha2', 'alpha3',
            'beta1', 'beta2', 'beta3',
            'rc1', 'rc2', 'rc3',
            'stable'
        );

        $aVersion    = (int) $aVersion;

        if (!$aVersion) {
            return '';
        }

        $status     = $aVersion % 10;
        $aVersion   = (int) ($aVersion / 10);
        $micro      = $aVersion % 1000;
        $aVersion   = (int) ($aVersion / 1000);
        $minor      = $aVersion % 1000;
        $major      = (int) ($aVersion / 1000);

        return "$major.$minor.$micro " . $mStatusName[$status];
    }

    /**
     * Format a version number to human readable with short status text
     * Example:
     * <code>
     * echo Format::shortVersion(10030042);
     * // will output: 1.3.4 a3
     * </code>
     *
     * @param int $aVersion The encoded version
     *
     * @return string  The version in human readable format
     */
    public static function repositoryShortVersion($aVersion)
    {
        $mShortStatusName = array(
            '&#945;1', '&#945;2', '&#945;3',
            '&#946;1', '&#946;2', '&#946;3',
            'r1', 'r2', 'r3',
            'st'
        );

        $aVersion    = (int) $aVersion;

        if (!$aVersion) {
            return '';
        }

        $status     = $aVersion % 10;
        $aVersion   = (int)($aVersion / 10);
        $micro      = $aVersion % 1000;
        $aVersion   = (int)($aVersion / 1000);
        $minor      = $aVersion % 1000;
        $major      = (int)($aVersion / 1000);

        return $status < 9 ? "$major.$minor.$micro ".$mShortStatusName[$status] : "$major.$minor.$micro";
    }
}
