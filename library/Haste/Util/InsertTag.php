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

class InsertTag
{
    /**
     * Recursively replace insert tags
     * @param    array|string
     * @return   array|string
     */
    public static function replaceRecursively($varValue)
    {
        if (is_array($varValue)) {
            foreach ($varValue as $k => $v) {
                $varValue[$k] = static::replaceRecursively($v);
            }

            return $varValue;

        } elseif (is_object($varValue)) {
            return $varValue;
        }

        return Haste::getInstance()->call('replaceInsertTags', array($varValue, false));
    }


    /**
     * Replace generally useful inserttags
     * @param   $strTag string
     * @return  string|false
     */
    public function replaceHasteInsertTags($strTag)
    {
        $arrTag = trimsplit('::', $strTag);

        // {{formatted_datetime::*}} insert tag
        // 4 possible use cases:
        //
        // {{formatted_datetime::timestamp}}
        //      or
        // {{formatted_datetime::timestamp::datim}}     - formats a given timestamp with the global date and time (datim) format
        // {{formatted_datetime::timestamp::date}}      - formats a given timestamp with the global date format
        // {{formatted_datetime::timestamp::time}}      - formats a given timestamp with the global time format
        // {{formatted_datetime::timestamp::Y-m-d H:i}} - formats a given timestamp with the specified format
        if ($arrTag[0] == 'formatted_datetime') {
            $intTimestamp = $arrTag[1];
            $strFormat = $arrTag[2];

            // Fallback
            if ($strFormat === null) {
                $strFormat = 'datim';
            }

            // Custom format
            if (!in_array($strFormat, array('datim', 'date', 'time'))) {
                return \Date::parse($strFormat, $intTimestamp);
            }

            return Format::$strFormat($intTimestamp);
        }

        return false;
    }
}
