<?php
/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2017 Codefog & terminal42 gmbh & RAD Consulting GmbH
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */
namespace Haste\Units\Dimension;

use Haste\Units\Converter;

/**
 * Class Unit
 */
class Unit extends Converter
{
    /**
     * Available dimension units
     */
    const METER = 'm';
    const DECIMETER = 'dm';
    const CENTIMETER = 'cm';
    const MILLIMETER = 'mm';
    const FOOT = 'ft';
    const INCH = 'in';

    /**
     * Conversion factor from base unit
     *
     * @var array
     */
    protected static $arrFactors = array(
        'm' => 0.01,
        'dm' => 0.1,
        'cm' => 1,
        'mm' => 10,
        'ft' => 0.0328084,
        'in' => 0.0833333,
    );

    /**
     * ISO abbrevations
     *
     * @var array
     */
    public static $arrISO = array(
        self::METER => 'MTR',
        self::DECIMETER => 'DMT',
        self::CENTIMETER => 'CMT',
        self::MILLIMETER => 'MMT',
        self::FOOT => 'FOT',
        self::INCH => 'INH',
    );

    /**
     * Get base unit for this dimension
     *
     * @return  string
     */
    public static function getBase()
    {
        return static::CENTIMETER;
    }

    /**
     * Get supported dimension units
     *
     * @return  array
     */
    public static function getAll()
    {
        return array(
            static::MILLIMETER,
            static::CENTIMETER,
            static::DECIMETER,
            static::METER,
            static::INCH,
            static::FOOT,
        );
    }
}
