<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *
 * PHP version 5
 * @copyright  terminal42 gmbh 2013
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Haste\Input;


class Id extends Input
{

    /**
     * Get numeric ID from URL parameter
     * @param   string
     * @return  int
     */
    public static function get($strKey)
    {
        $strValue = static::getAutoItem($strKey);

        return (int) strtok($strValue, '-');
    }

    /**
     * Validate name for ID in URL and redirect if necessary
     * @param   string
     * @param   int
     * @param   string
     */
    public static function validateName($strKey, $strName)
    {
        $intId = static::get($strKey);
        $strValid = $intId . '-' . standardize($strName);

        if (static::getAutoItem($strKey) != $strValid) {
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
