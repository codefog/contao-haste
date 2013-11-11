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

class Input extends \Input
{

    /**
     * Check if auto_item is enabled and return appropriate value
     * @param   string
     * @return  string
     */
    public static function getAutoItem($strKey)
    {
        if ($GLOBALS['TL_CONFIG']['useAutoItem'] && in_array($strKey, $GLOBALS['TL_AUTO_ITEM'])) {

            return static::get('auto_item');
        }

        return static::get($strKey);
    }
}
