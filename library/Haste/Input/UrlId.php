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

namespace Haste\Input;

class UrlId
{

    /**
     * Get numeric ID from URL parameter
     * @param   string
     * @return  int
     */
    public static function get($strKey)
    {
        $strValue = Input::getAutoItem($strKey);

        return (int) strtok($strValue, '-');
    }

    /**
     * Validate name for ID in URL and redirect if necessary
     * @param   string
     * @param   int
     * @param   string
     */
    public static function validate($strKey, $intId, $strName)
    {
        $strValid = $intId . '-' . standardize($strName);

        if (Input::getAutoItem($strKey) != $strValid) {

            /** @type \PageModel $objPage */
            global $objPage;

            $strParams = '/' . $strValid;

            // Check if key is auto_item enabled
            if (!$GLOBALS['TL_CONFIG']['useAutoItem'] || !in_array($strKey, $GLOBALS['TL_AUTO_ITEM'])) {
                $strParams = '/' . $strKey . $strParams;
            }

            \System::redirect($objPage->getFrontendUrl($strParams), 301);
        }
    }
}
