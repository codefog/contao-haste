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

class Scale
{

    /**
     * Available weight units
     */
    const UNIT_MILIGRAM = 'mg';
    const UNIT_GRAM = 'g';
    const UNIT_KILOGRAM = 'kg';
    const UNIT_METRICTON = 't';
    const UNIT_CARAT = 'cz';
    const UNIT_OUNCE = 'oz';
    const UNIT_POUND = 'lb';
    const UNIT_STONE = 'st';
    const UNIT_GRAIN = 'grain';


    /**
     * Scale weight objects
     * @var array
     */
    protected $arrWeight = array();


    /**
     * Add weight to the scale
     * @param   WeightInterface
     */
    public function add(WeightInterface $objWeight)
    {
        $this->arrWeight[] = $objWeight;

        return $this;
    }


    /**
     * Remove a weight object from scale
     * @param   WeightInterface
     * @return  self
     */
    public function remove(WeightInterface $objWeight)
    {
        $key = array_search($objWeight, $this->arrWeight, true);

        if ($key !== false) {
            unset($this->arrWeight[$key]);
        }

        return $this;
    }

    /**
     * Standardize and calculate the total of multiple weights
     *
     * It's probably faster in theory to convert only the total to the final unit, and not each product weight.
     * However, we might loose precision, not sure about that.
     * Based on formulas found at http://jumk.de/calc/gewicht.shtml
     * @param array
     * @param string
     * @return mixed
     */
    public function amountIn($strUnit)
    {
        if (empty($this->arrWeights)) {
            return 0;
        }

        $fltWeight = 0;

        foreach ($this->arrWeights as $objWeight) {
            if ($objWeight->getWeightValue() > 0) {
                $fltWeight += static::convertWeight(floatval($objWeight->getWeightValue()), $objWeight->getWeightUnit(), static::UNIT_KILOGRAM);
            }
        }

        return static::convertWeight($fltWeight, static::UNIT_KILOGRAM, $strUnit);
    }

    /**
     * Check if weight on scale is less than given weight
     * @param   WeightInterface
     * @return  bool
     */
    public function isLessThan(WeightInterface $objWeight)
    {
        return $this->amountIn($objWeight->getWeightUnit()) < $objWeight->getWeightValue();
    }

    /**
     * Check if weight on scale is equal to or less than given weight
     * @param   WeightInterface
     * @return  bool
     */
    public function isEqualOrLessThan(WeightInterface $objWeight)
    {
        return $this->amountIn($objWeight->getWeightUnit()) <= $objWeight->getWeightValue();
    }

    /**
     * Check if weight on scale is more than given weight
     * @param   WeightInterface
     * @return  bool
     */
    public function isMoreThan(WeightInterface $objWeight)
    {
        return $this->amountIn($objWeight->getWeightUnit()) > $objWeight->getWeightValue();
    }

    /**
     * Check if weight on scale is equal to or more than given weight
     * @param   WeightInterface
     * @return  bool
     */
    public function isEqualOrMoreThan(WeightInterface $objWeight)
    {
        return $this->amountIn($objWeight->getWeightUnit()) >= $objWeight->getWeightValue();
    }


    /**
     * Convert weight units
     * Supported source/target units: mg, g, kg, t, ct, oz, lb, st, grain
     * @param float
     * @param string
     * @param string
     * @return mixed
     * @throws Exception
     */
    public static function convertWeight($fltWeight, $strSourceUnit, $strTargetUnit)
    {
        switch ($strSourceUnit) {

            case static::UNIT_MILIGRAM:
                return static::convertWeight(($fltWeight / 1000000), static::UNIT_KILOGRAM, $strTargetUnit);

            case static::UNIT_GRAM:
                return static::convertWeight(($fltWeight / 1000), static::UNIT_KILOGRAM, $strTargetUnit);

            case static::UNIT_KILOGRAM:
                switch ($strTargetUnit) {

                    case static::UNIT_MILIGRAM:
                        return $fltWeight * 1000000;

                    case static::UNIT_GRAM:
                        return $fltWeight * 1000;

                    case static::UNIT_KILOGRAM:
                        return $fltWeight;

                    case static::UNIT_METRICTON:
                        return $fltWeight / 1000;

                    case static::UNIT_CARAT:
                        return $fltWeight * 5000;

                    case static::UNIT_OUNCE:
                        return $fltWeight / 28.35 * 1000;

                    case static::UNIT_POUNT:
                        return $fltWeight / 0.45359243;

                    case static::UNIT_STONE:
                        return $fltWeight / 6.35029318;

                    case static::UNIT_GRAIN:
                        return $fltWeight / 64.79891 * 1000000;

                    default:
                        throw new \InvalidArgumentException('Unknown target weight unit "' . $strTargetUnit . '"');
                }

            case static::UNIT_METRICTON:
                return static::convertWeight(($fltWeight * 1000), static::UNIT_KILOGRAM, $strTargetUnit);

            case static::UNIT_CARAT:
                return static::convertWeight(($fltWeight / 5000), static::UNIT_KILOGRAM, $strTargetUnit);

            case static::UNIT_OUNCE:
                return static::convertWeight(($fltWeight * 28.35 / 1000), static::UNIT_KILOGRAM, $strTargetUnit);

            case static::UNIT_POUND:
                return static::convertWeight(($fltWeight * 0.45359243), static::UNIT_KILOGRAM, $strTargetUnit);

            case static::UNIT_STONE:
                return static::convertWeight(($fltWeight * 6.35029318), static::UNIT_KILOGRAM, $strTargetUnit);

            case static::UNIT_GRAIN:
                return static::convertWeight(($fltWeight * 64.79891 / 1000000), static::UNIT_KILOGRAM, $strTargetUnit);

            default:
                throw new \InvalidArgumentException('Unknown source weight unit "' . $strSourceUnit . '"');
        }
    }

    /**
     * Get supported weight units
     * @return  array
     */
    public static function getUnits()
    {
        return array(
            static::UNIT_MILIGRAM,
            static::UNIT_GRAM,
            static::UNIT_KILOGRAM,
            static::UNIT_METRICTON,
            static::UNIT_CARAT,
            static::UNIT_OUNCE,
            static::UNIT_POUND,
            static::UNIT_STONE,
            static::UNIT_GRAIN,
        );
    }
}
