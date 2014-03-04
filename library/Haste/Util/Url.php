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
     * Add a request string to the given URI string or page ID
     * @param   string
     * @param   mixed
     * @return  string
     * @throws  \InvalidArgumentException
     */
    public static function addQueryString($strRequest, $varUrl=null)
    {
        $strUrl = static::prepareUrl($varUrl);

        if ($strRequest === '') {
            return $strUrl;
        }

        list($strScript, $strQueryString) = explode('?', $strUrl, 2);

        $strRequest = preg_replace('/^&(amp;)?/i', '', $strRequest);
        $queries = preg_split('/&(amp;)?/i', $strQueryString, PREG_SPLIT_NO_EMPTY);

        // Overwrite existing parameters and ignore "language", see #64
        foreach ($queries as $k=>$v) {
            $explode = explode('=', $v, 2);

            if ($v === '' || $k === 'language' || preg_match('/(^|&(amp;)?)' . preg_quote($explode[0], '/') . '=/i', $strRequest)) {
                unset($queries[$k]);
            }
        }

        $href = '?';

        if (!empty($queries)) {
            $href .= implode('&amp;', $queries) . '&amp;';
        }

        return $strScript . $href . str_replace(' ', '%20', $strRequest);
    }

    /**
     * Prepare URL from ID and keep query string from current string
     * @param   mixed
     * @return  string
     */
    protected static function prepareUrl($varUrl)
    {
        if ($varUrl === null) {
            $varUrl = \Environment::getInstance()->request;

        } elseif (is_numeric($varUrl)) {
            if (($objJump = \PageModel::findByPk($varUrl)) === null) {
                throw new \InvalidArgumentException('Given page id does not exist.');
            }

            $varUrl = \Controller::generateFrontendUrl($objJump->row());

            list(, $strQueryString) = explode('?', \Environment::getInstance()->request, 2);

            if ($strQueryString != '') {
                $varUrl .= '?' . $strQueryString;
            }
        }

        return $varUrl;
    }
}
