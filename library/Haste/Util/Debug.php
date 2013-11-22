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

class Debug
{

    /**
     * Returns true if debug mode is enabled
     * @return  bool
     */
    public static function isActive()
    {
        return $GLOBALS['TL_CONFIG']['debugMode'];
    }

    /**
     * Get uncompressed version (file path) of a file if debug is enabled
     * @param   string
     * @return  string
     */
    public static function uncompressedFile($strFile)
    {
        if (!static::isActive()) {
            return $strFile;
        }

        return str_replace('.min.', '.', $strFile);
    }

    /**
     * Add a message to debug console, if console is enabled
     * @param   string
     * @param   string
     */
    public static function addToConsole($strMessage, $strGroup='other')
    {
        if (!static::isActive()) {
            return;
        }

        if ($strMessage == '' || $strGroup == '') {
            throw new \InvalidArgumentException('Message or group parameter is empty.');
        }

        $GLOBALS['TL_DEBUG'][$strGroup][] = $strMessage;
    }
}
