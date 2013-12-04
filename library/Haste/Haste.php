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
     * Haste version
     */
    const VERSION = '3.0.2';

    /**
     * Current object instance (Singleton)
     * @var Haste
     */
    protected static $objInstance;

    /**
     * Allow access to all protected Contao Controller methods
     * @param   string Method name
     * @param   mixed Arguments
     */
    public function call($name, $arguments=null)
    {
        $arguments = $arguments === null ? array() : (is_array($arguments) ? $arguments : array($arguments));

        return call_user_func_array(array($this, $name), $arguments);
    }

    /**
     * Instantiate the Haste object
     * @return  object
     */
    public static function getInstance()
    {
        if (null === static::$objInstance) {
            static::$objInstance = new static();
        }

        return static::$objInstance;
    }
}
