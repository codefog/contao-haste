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

namespace Haste\Weight;

class Weight implements Weighable
{

    /**
     * Weight amount
     * @param   float
     */
    protected $fltValue;

    /**
     * Weight unit
     * @param   string
     */
    protected $strUnit;


    public function __construct($fltValue, $strUnit)
    {
        $this->fltValue = $fltValue;
        $this->strUnit = (string) $strUnit;
    }

    public function getWeightValue()
    {
        return $this->fltValue;
    }

    public function getWeightUnit()
    {
        return $this->strUnit;
    }

    /**
     * Create weight object from timePeriod widget value
     * @param   mixed
     * @return  Weight|null
     */
    public static function createFromTimePeriod($arrData)
    {
        $arrUnits =
        $arrData = deserialize($arrData);

        if (empty($arrData) || !is_array($arrData) || !in_array($arrData['unit'], Scale::getUnits())) {
            return null;
        }

        return new static($arrData['value'], $arrData['unit']);
    }
}
