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
     * Return a $_GET variable or a default value
     *
     * @param string $strKey            The variable name
     * @param mixed  $varDefaultValue   The default value
     * @param bool   $blnDecodeEntities If true, all entities will be decoded
     * @param bool   $blnKeepUnused     If true, the parameter will not be marked as used (see #4277)
     *
     * @return null
     */
    public static function get($strKey, $varDefaultValue=null, $blnDecodeEntities=false, $blnKeepUnused=false)
    {
        return parent::get($strKey, $blnDecodeEntities, $blnKeepUnused) ?: $varDefaultValue;
    }


    /**
     * Return a $_POST variable or a default value
     *
     * @param string  $strKey            The variable name
     * @param mixed   $varDefaultValue   The default value
     * @param boolean $blnDecodeEntities If true, all entities will be decoded
     *
     * @return mixed The cleaned variable value
     */
    public static function post($strKey, $varDefaultValue=null, $blnDecodeEntities=false)
    {
        return parent::post($strKey, $blnDecodeEntities) ?: $varDefaultValue;
    }


    /**
     * Return a $_POST variable preserving allowed HTML tags or a default value
     *
     * @param string  $strKey            The variable name
     * @param mixed   $varDefaultValue   The default value
     * @param boolean $blnDecodeEntities If true, all entities will be decoded
     *
     * @return mixed The cleaned variable value
     */
    public static function postHtml($strKey, $varDefaultValue=null, $blnDecodeEntities=false)
    {
        return parent::postHtml($strKey, $blnDecodeEntities) ?: $varDefaultValue;
    }


    /**
     * Return a raw, unsafe $_POST variable or a default value
     *
     * @param string $strKey          The variable name
     * @param mixed  $varDefaultValue The default value
     *
     * @return mixed The raw variable value
     */
    public static function postRaw($strKey, $varDefaultValue=null)
    {
        return parent::postRaw($strKey) ?: $varDefaultValue;
    }


    /**
     * Return a raw, unsafe and unfiltered $_POST variable or a default value
     *
     * @param string $strKey          The variable name
     * @param mixed  $varDefaultValue The default value
     *
     * @return mixed The raw variable value
     */
    public static function postUnsafeRaw($strKey, $varDefaultValue=null)
    {
        return parent::postUnsafeRaw($strKey) ?: $varDefaultValue;
    }


    /**
     * Check if auto_item is enabled and return $_GET variable
     *
     * @param string  $strKey            The variable name
     * @param boolean $blnDecodeEntities If true, all entities will be decoded
     * @param boolean $blnKeepUnused     If true, the parameter will not be marked as used (see #4277)
     *
     * @return mixed The cleaned variable value
     */
    public static function getAutoItem($strKey, $blnDecodeEntities=false, $blnKeepUnused=false)
    {
        if ($GLOBALS['TL_CONFIG']['useAutoItem'] && in_array($strKey, $GLOBALS['TL_AUTO_ITEM'])) {
            $strKey = 'auto_item';
        }

        // Fix "unused" bug in Contao Core (see https://github.com/contao/core/issues/7185)
        if (!$blnKeepUnused) {
            unset(static::$arrUnusedGet[$strKey]);
        }

        return static::get($strKey, $blnDecodeEntities, $blnKeepUnused);
    }

}
