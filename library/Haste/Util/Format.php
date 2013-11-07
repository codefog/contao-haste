<?php

/**
 * Haste unilities for Contao Open Source CMS
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
        }

        return $varValue;
    }
}
