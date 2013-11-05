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

namespace Haste\Units;

abstract class Converter
{

    /**
     * Convert from one to another unit
     * @param   float
     * @param   string
     * @param   string
     * @return  float
     */
    public static function convert($varValue, $strSourceUnit, $strTargetUnit)
    {
        if ($strSourceUnit == $strTargetUnit) {
            return $varValue;
        }

        if ($strSourceUnit != static::getBase()) {
            $varValue = ($varValue / static::getFactor($strSourceUnit));
        }

        return $varValue * static::getFactor($strTargetUnit);
    }

    /**
     * Return factor to calculate the unit to the base unit
     * @param   string
     * @return  float
     */
    public static function getFactor($strUnit)
    {
        return static::$arrFactors[$strUnit];
    }

    abstract public static function getAll();

    abstract public static function getBase();
}
