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
namespace Codefog\HasteBundle\Units\Volume;

use Codefog\HasteBundle\Units\Converter;

/**
 * Class Unit
 */
class Unit extends Converter
{
    /**
     * Available volume units
     */
    const CUBICMETER = 'm3';
    const CUBICDECIMETER = 'dm3';
    const CUBICCENTIMETER = 'cm3';
    const CUBICMILLIMETER = 'mm3';
    const CUBICFOOT = 'ft3';
    const CUBICINCH = 'in3';
    const LITER = 'l';
    const DECILITER = 'dl';
    const CENTILITER = 'cl';
    const MILLILITER = 'ml';

    /**
     * Conversion factor from base unit
     *
     * @var array
     */
    protected static $arrFactors = array(
        'm3' => 0.001,
        'dm3' => 1,
        'cm3' => 1000,
        'mm3' => 1000000,
        'ft3' => 0.0353147,
        'in3' => 61.0237,
        'l' => 1,
        'dl' => 10,
        'cl' => 100,
        'ml' => 1000,
    );

    /**
     * ISO abbrevations
     *
     * @var array
     */
    public static $arrISO = array(
        self::CUBICMETER => 'MTQ',
        self::CUBICDECIMETER => 'DMQ',
        self::CUBICCENTIMETER => 'CMQ',
        self::CUBICMILLIMETER => 'MMQ',
        self::CUBICFOOT => 'FTQ',
        self::CUBICINCH => 'INQ',
    );

    /**
     * Get base unit for this dimension
     *
     * @return  string
     */
    public static function getBase()
    {
        return static::LITER;
    }

    /**
     * Get supported dimension units
     *
     * @return  array
     */
    public static function getAll()
    {
        return array(
            static::LITER,
            static::DECILITER,
            static::CENTILITER,
            static::MILLILITER,
            static::CUBICMILLIMETER,
            static::CUBICCENTIMETER,
            static::CUBICDECIMETER,
            static::CUBICMETER,
            static::CUBICINCH,
            static::CUBICFOOT,
        );
    }
}
