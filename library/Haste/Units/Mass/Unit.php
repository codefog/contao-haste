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

namespace Haste\Units\Mass;

use Haste\Units\Converter;

class Unit extends Converter
{

    /**
     * Available weight units
     */
    const MILIGRAM = 'mg';
    const GRAM = 'g';
    const KILOGRAM = 'kg';
    const METRICTON = 't';
    const CARAT = 'cz';
    const OUNCE = 'oz';
    const POUND = 'lb';
    const STONE = 'st';
    const GRAIN = 'grain';

    /**
     * Conversion factor from base unit
     * @var array
     */
    protected static $arrFactors = array(
        'mg'    => 1000000,
        'g'     => 1000,
        'kg'    => 1,
        't'     => 0.001,
        'cz'    => 5000,
        'oz'    => 35.2733686067,
        'lb'    => 2.2046226218487757,
        'st'    => 0.1574730444,
        'grain' => 15432.3583529414
    );

    /**
     * Get base unit for this mass
     * @return  string
     */
    public static function getBase()
    {
        return static::KILOGRAM;
    }

    /**
     * Get supported weight units
     * @return  array
     */
    public static function getAll()
    {
        return array(
            static::MILIGRAM,
            static::GRAM,
            static::KILOGRAM,
            static::METRICTON,
            static::CARAT,
            static::OUNCE,
            static::POUND,
            static::STONE,
            static::GRAIN,
        );
    }
}
