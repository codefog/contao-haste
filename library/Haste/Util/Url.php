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

class Url
{

    /**
     * Add a query string to the given URI string or page ID
     * @param   string Query
     * @param   mixed
     * @return  string
     * @throws  \InvalidArgumentException
     */
    public static function addQueryString($strQuery, $varUrl=null)
    {
        $strUrl = static::prepareUrl($varUrl);
        $strQuery = trim(ampersand($strQuery, false), '&');

        list($strScript, $strQueryString) = explode('?', $strUrl, 2);

        $queries = explode('&', $strQueryString);

        // Overwrite existing parameters and ignore "language", see #64
        foreach ($queries as $k => $v) {
            $explode = explode('=', $v, 2);

            if ($v === '' || $k === 'language' || preg_match('/(^|&(amp;)?)' . preg_quote($explode[0], '/') . '=/i', $strQuery)) {
                unset($queries[$k]);
            }
        }

        $href = '';

        if (!empty($queries)) {
            $href = '?' . implode('&', $queries) . '&';
        } elseif (!empty($strQuery)) {
            $href = '?';
        }

        return $strScript . $href . $strQuery;
    }

    /**
     * Remove query parameters from the current URL
     * @param   array
     * @param   mixed
     * @return  string
     */
    public static function removeQueryString(array $arrParams, $varUrl=null)
    {
        $strUrl = static::prepareUrl($varUrl);

        if (empty($arrParams)) {
            return $strUrl;
        }

        list($strScript, $strQueryString) = explode('?', $strUrl, 2);

        $queries = explode('&', $strQueryString);

        // Remove given parameters
        foreach ($queries as $k => $v) {
            $explode = explode('=', $v, 2);

            if ($v === '' || in_array($explode[0], $arrParams)) {
                unset($queries[$k]);
            }
        }

        $href = '';

        if (!empty($queries)) {
            $href .= '?' . implode('&', $queries);
        }

        return $strScript . $href;
    }

    /**
     * Prepare URL from ID and keep query string from current string
     * @param   mixed
     * @return  string
     */
    protected static function prepareUrl($varUrl)
    {
        if ($varUrl === null) {
            $varUrl = \Environment::get('request');

        } elseif (is_numeric($varUrl)) {
            if (($objJump = \PageModel::findByPk($varUrl)) === null) {
                throw new \InvalidArgumentException('Given page id does not exist.');
            }

            $varUrl = \Controller::generateFrontendUrl($objJump->row());

            list(, $strQueryString) = explode('?', \Environment::get('request'), 2);

            if ($strQueryString != '') {
                $varUrl .= '?' . $strQueryString;
            }
        }

        $varUrl = ampersand($varUrl, false);

        return $varUrl;
    }
}
