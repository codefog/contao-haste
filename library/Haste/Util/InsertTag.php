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
     *
     * @param array|string $varValue
     *
     * @return array|string
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

        return \Controller::replaceInsertTags($varValue, false);
    }


    /**
     * Replace generally useful insert tags
     *
     * @param string $strTag
     *
     * @return string|false
     */
    public function replaceHasteInsertTags($strTag)
    {
        $arrTag = trimsplit('::', $strTag);

        if ($arrTag[0] == 'formatted_datetime') {
            return $this->replaceFormattedDateTime($arrTag);
        }

        if ($arrTag[0] == 'dca_label') {
            return $this->replaceDcaLabel($arrTag);
        }

        if ($arrTag[0] == 'dca_value') {
            return $this->replaceDcaValue($arrTag);
        }

        if ($arrTag[0] == 'rand') {
            return (count($arrTag) === 3) ? mt_rand((int) $arrTag[1], (int) $arrTag[2]) : mt_rand();
        }

        if ($arrTag[0] == 'flag') {
            return (string) $arrTag[1];
        }

        if ($arrTag[0] == 'options_label') {
            return $this->replaceOptionsLabel($arrTag);
        }

        return false;
    }


    /**
     * Replace {{formatted_datetime::*}} insert tag
     *
     * 4 possible use cases:
     *
     * {{formatted_datetime::timestamp}}
     *      or
     * {{formatted_datetime::timestamp::datim}}     - formats a given timestamp with the global date and time (datim) format
     * {{formatted_datetime::timestamp::date}}      - formats a given timestamp with the global date format
     * {{formatted_datetime::timestamp::time}}      - formats a given timestamp with the global time format
     * {{formatted_datetime::timestamp::Y-m-d H:i}} - formats a given timestamp with the specified format
     *
     * @param array $arrTag
     *
     * @return string
     */
    private function replaceFormattedDateTime($arrTag)
    {
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


    /**
     * Replace {{dca_label::*}} insert tag
     *
     * use case:
     *
     * {{dca_label::table::field}}
     *
     * @param array $arrTag
     *
     * @return string
     */
    private function replaceDcaLabel($arrTag)
    {
        $strTable = $arrTag[1];
        $strField = $arrTag[2];

        return Format::dcaLabel($strTable, $strField);
    }


    /**
     * Replace {{dca_value::*}} insert tag
     *
     * use case:
     *
     * {{dca_value::table::field::value}}
     *
     * @param array $arrTag
     *
     * @return string
     */
    private function replaceDcaValue($arrTag)
    {
        $strTable = $arrTag[1];
        $strField = $arrTag[2];
        $varValue = $arrTag[3];

        return Format::dcaValue($strTable, $strField, $varValue);
    }

    /**
     * Replace {{option_label::*}} insert tag
     *
     * use case:
     *
     * {{option_label::ID::value}}
     *
     * @param array $arrTag
     */
    private function replaceOptionsLabel($arrTag)
    {
        $id    = $arrTag[1];
        $value = $arrTag[2];
        $field = \FormFieldModel::findByPk($id);

        if (null === $field) {
            return $value;
        }

        $options = deserialize($field->options);

        if (empty($options) || !is_array($options)) {
            return $value;
        }

        foreach ($options as $option) {
            if ($value == $option['value']) {
                return $option['label'];
            }
        }

        return $value;
    }
}
