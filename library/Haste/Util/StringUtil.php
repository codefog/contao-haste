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

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class StringUtil
{
    /**
     * Text filter options
     */
    const NO_TAGS = 1;
    const NO_BREAKS = 2;
    const NO_EMAILS = 4;


    /**
     * Recursively replace simple tokens and insert tags
     *
     * @param string $strText
     * @param array  $arrTokens    Array of Tokens
     * @param int    $intTextFlags Filters the tokens and the text for a given set of options
     *
     * @return string
     */
    public static function recursiveReplaceTokensAndTags($strText, $arrTokens, $intTextFlags = 0)
    {
        if ($intTextFlags > 0) {
            $arrTokens = static::convertToText($arrTokens, $intTextFlags);
        }

        // PHP 7 compatibility
        // See #309 (https://github.com/contao/core-bundle/issues/309)
        if (version_compare(VERSION . '.' . BUILD, '3.5.1', '>=')) {
            // Must decode, tokens could be encoded
            $strText = \StringUtil::decodeEntities($strText);
        } else {
            // Must decode, tokens could be encoded
            $strText = \String::decodeEntities($strText);
        }

        // Replace all opening and closing tags with a hash so they don't get stripped
        // by parseSimpleTokens() - this is useful e.g. for XML content
        $strHash                = md5($strText);
        $strTagOpenReplacement  = 'HASTE-TAG-OPEN-' . $strHash;
        $strTagCloseReplacement = 'HASTE-TAG-CLOSE-' . $strHash;
        $arrOriginal            = array('<', '>');
        $arrReplacement         = array($strTagOpenReplacement, $strTagCloseReplacement);

        $strText = str_replace($arrOriginal, $arrReplacement, $strText);

        // PHP 7 compatibility
        // See #309 (https://github.com/contao/core-bundle/issues/309)
        if (version_compare(VERSION . '.' . BUILD, '3.5.1', '>=')) {
            // first parse the tokens as they might have if-else clauses
            $strBuffer = \StringUtil::parseSimpleTokens($strText, $arrTokens);
        } else {
            // first parse the tokens as they might have if-else clauses
            $strBuffer = \String::parseSimpleTokens($strText, $arrTokens);
        }

        $strBuffer = str_replace($arrReplacement, $arrOriginal, $strBuffer);

        // then replace the insert tags
        $strBuffer = \Controller::replaceInsertTags($strBuffer, false);

        // check if the inserttags have returned a simple token or an insert tag to parse
        if ((strpos($strBuffer, '##') !== false || strpos($strBuffer, '{{') !== false) && $strBuffer != $strText) {
            $strBuffer = static::recursiveReplaceTokensAndTags($strBuffer, $arrTokens, $intTextFlags);
        }

        // PHP 7 compatibility
        // See #309 (https://github.com/contao/core-bundle/issues/309)
        if (version_compare(VERSION . '.' . BUILD, '3.5.1', '>=')) {
            $strBuffer = \StringUtil::restoreBasicEntities($strBuffer);
        } else {
            $strBuffer = \String::restoreBasicEntities($strBuffer);
        }

        if ($intTextFlags > 0) {
            $strBuffer = static::convertToText($strBuffer, $intTextFlags);
        }

        return $strBuffer;
    }

    /**
     * Convert the given array or string to plain text using given options
     *
     * @param mixed $varValue
     * @param int   $options
     *
     * @return mixed
     */
    public static function convertToText($varValue, $options)
    {
        if (is_array($varValue)) {
            foreach ($varValue as $k => $v) {
                $varValue[$k] = static::convertToText($v, $options);
            }

            return $varValue;
        }

        // Replace friendly email before stripping tags
        if (!($options & static::NO_EMAILS)) {
            $arrEmails = array();
            preg_match_all('{<.+@.+\.[A-Za-z]{2,6}>}', $varValue, $arrEmails);

            if (!empty($arrEmails[0])) {
                foreach ($arrEmails[0] as $k => $v) {
                    $varValue = str_replace($v, '%email' . $k . '%', $varValue);
                }
            }
        }

        // Remove HTML tags but keep line breaks for <br> and <p>
        if ($options & static::NO_TAGS) {
            $varValue = strip_tags(preg_replace('{(?!^)<(br|p|/p).*?/?>\n?(?!$)}is', "\n", $varValue));
        }

        // Remove line breaks (e.g. for subject)
        if ($options & static::NO_BREAKS) {
            $varValue = str_replace(array("\r", "\n"), '', $varValue);
        }

        // Restore friendly email after stripping tags
        if (!($options & static::NO_EMAILS) && !empty($arrEmails[0])) {
            foreach ($arrEmails[0] as $k => $v) {
                $varValue = str_replace('%email' . $k . '%', $v, $varValue);
            }
        }

        return $varValue;
    }

    /**
     * Flatten input data, Simple Tokens can't handle arrays
     *
     * @param mixed  $varValue
     * @param string $strKey
     * @param array  $arrData
     */
    public static function flatten($varValue, $strKey, array &$arrData)
    {
        if (is_object($varValue)) {
            return;
        } elseif (!is_array($varValue)) {
            $arrData[$strKey] = $varValue;
            return;
        }

        $blnAssoc = array_is_assoc($varValue);
        $arrValues = array();

        foreach ($varValue as $k => $v) {
            if ($blnAssoc || is_array($v)) {
                static::flatten($v, $strKey.'_'.$k, $arrData);
            } else {
                $arrData[$strKey.'_'.$v] = '1';
                $arrValues[]             = $v;
            }
        }

        $arrData[$strKey] = implode(', ', $arrValues);
    }
}
