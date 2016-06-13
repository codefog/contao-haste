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

namespace Haste;

class Haste extends \Controller
{
    /**
     * Current object instance (Singleton)
     * @var Haste
     */
    protected static $objInstance;

    /**
     * Allow access to all protected Contao Controller methods
     *
     * @param string $name Method name
     * @param mixed  $arguments
     *
     * @return mixed
     *
     * @deprecated Deprecated since Haste 4.13, to be removed in Haste 5.0
     *             No longer needed because necessary Contao core methods are all public static.
     */
    public function call($name, $arguments = null)
    {
        $arguments = $arguments === null ? array() : (is_array($arguments) ? $arguments : array($arguments));

        return call_user_func_array(array($this, $name), $arguments);
    }

    /**
     * Instantiate the Haste object
     * @return static
     */
    public static function getInstance()
    {
        if (null === static::$objInstance) {
            static::$objInstance = new static();
        }

        return static::$objInstance;
    }

    /**
     * Recursively create a directory
     * @param   string
     * @param   bool
     * @return  bool
     * @deprecated use `new Folder(...)` (see https://github.com/contao/core/issues/6553)
     */
    public static function mkdirr($strDirectory)
    {
        $components = explode('/', $strDirectory);
        $strDirectory = '';

        foreach ($components as $folder) {

            $strDirectory .= '/' . (string) $folder;
            $strDirectory = ltrim($strDirectory, '/');

            // Does not matter if file or directory
            if (!file_exists(TL_ROOT . '/' . $strDirectory)) {
                if (!\Files::getInstance()->mkdir($strDirectory)) {
                    return false;
                }
            }
        }

        return is_dir(TL_ROOT . '/' . $strDirectory);
    }
}
