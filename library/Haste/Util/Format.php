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
     * @param   string
     * @param   string
     */
    public static function dcaLabel($strTable, $strField)
    {
        \System::loadLanguageFile($strTable);
        \Haste\Haste::getInstance()->call('loadDataContainer', $strTable);

        if (!empty($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['label'])) {
            $strLabel = is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['label']) ? $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['label'][0] : $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['label'];
        } else {
            $strLabel = is_array($GLOBALS['TL_LANG']['MSC'][$strField]) ? $GLOBALS['TL_LANG']['MSC'][$strField][0] : $GLOBALS['TL_LANG']['MSC'][$strField];
        }

        if ($strLabel == '') {
            $strLabel = $strField;
        }

        return $strLabel;
    }


    /**
     * Format DCA field value according to Contao Core standard
     * @param   string
     * @param   string
     * @param   mixed
     * @return  string
     */
    public static function dcaValue($strTable, $strField, $varValue)
    {
        $varValue = deserialize($varValue);

        \System::loadLanguageFile($strTable);
        \Haste\Haste::getInstance()->call('loadDataContainer', $strTable);

        // Get field value
        if (strlen($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['foreignKey'])) {
            $chunks = explode('.', $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['foreignKey']);
            $varValue = empty($varValue) ? array(0) : $varValue;
            $objKey = \Database::getInstance()->execute("SELECT " . $chunks[1] . " AS value FROM " . $chunks[0] . " WHERE id IN (" . implode(',', array_map('intval', (array) $varValue)) . ")");

            return implode(', ', $objKey->fetchEach('value'));

        } elseif (is_array($varValue)) {
            foreach ($varValue as $kk => $vv) {
                $varValue[$kk] = static::dcaValue($strTable, $strField, $vv);
            }

            return implode(', ', $varValue);

        } elseif ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['rgxp'] == 'date') {
            return static::date($varValue);

        } elseif ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['rgxp'] == 'time') {
            return static::time($varValue);

        } elseif ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['rgxp'] == 'datim' || in_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['flag'], array(5, 6, 7, 8, 9, 10)) || $strField == 'tstamp') {
            return static::datim($varValue);

        } elseif ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['multiple']) {
            return strlen($varValue) ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];

        } elseif ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['inputType'] == 'textarea' && ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['allowHtml'] || $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['preserveTags'])) {
            return specialchars($varValue);

        } elseif (is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['reference'])) {
            return isset($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['reference'][$varValue]) ? ((is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['reference'][$varValue])) ? $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['reference'][$varValue][0] : $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['reference'][$varValue]) : $varValue;

        } elseif ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['options'])) {
            return isset($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['options'][$varValue]) ? ((is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['options'][$varValue])) ? $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['options'][$varValue][0] : $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['options'][$varValue]) : $varValue;
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
     * @param   int     The encoded version
     * @return  string  The version in human readable format
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
     *
     * Example:
     * <code>
     * echo Format::shortVersion(10030042);
     * // will output: 1.3.4 a3
     * </code>
     * @param   int     The encoded version
     * @return  string  The version in human readable format
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
