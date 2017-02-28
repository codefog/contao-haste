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

/**
 * Class Scale
 */
class Scale
{
    /**
     * Scale dimension objects
     *
     * @var Dimension[]
     */
    protected $arrDimensions = array();

    /**
     * Add dimension to the scale
     *
     * @param Measurable $objDimension
     * @return $this
     */
    public function add(Measurable $objDimension)
    {
        $this->arrDimensions[] = $objDimension;

        return $this;
    }

    /**
     * Remove a dimension object from scale
     *
     * @param   Measurable
     * @return  self
     */
    public function remove(Measurable $objDimension)
    {
        $key = array_search($objDimension, $this->arrDimensions, true);

        if ($key !== false) {
            unset($this->arrDimensions[$key]);
        }

        return $this;
    }

    /**
     * Standardize and calculate the total of multiple dimension
     *
     * It's probably faster in theory to convert only the total to the final unit, and not each product dimension.
     * However, we might loose precision, not sure about that.
     * Based on formulas found at https://jumk.de/calc/
     *
     * @param array
     * @param string
     * @return mixed
     */
    public function amountIn($strUnit)
    {
        if (empty($this->arrDimensions)) {
            return 0;
        }

        $fltDimension = 0;

        foreach ($this->arrDimensions as $objDimension) {
            if ($objDimension->getDimensionValue() > 0) {
                $fltDimension += Unit::convert(floatval($objDimension->getDimensionValue()), $objDimension->getDimensionUnit(), Unit::CENTIMETER);
            }
        }

        return Unit::convert($fltDimension, Unit::CENTIMETER, $strUnit);
    }

    /**
     * Check if dimension on scale is less than given dimension
     *
     * @param   Measurable $objDimension
     * @return  bool
     */
    public function isLessThan(Measurable $objDimension)
    {
        return $this->amountIn($objDimension->getDimensionUnit()) < $objDimension->getDimensionValue();
    }

    /**
     * Check if dimension on scale is equal to or less than given dimension
     *
     * @param   Measurable $objDimension
     * @return  bool
     */
    public function isEqualOrLessThan(Measurable $objDimension)
    {
        return $this->amountIn($objDimension->getDimensionUnit()) <= $objDimension->getDimensionValue();
    }

    /**
     * Check if dimension on scale is more than given dimension
     *
     * @param   Measurable $objDimension
     * @return  bool
     */
    public function isMoreThan(Measurable $objDimension)
    {
        return $this->amountIn($objDimension->getDimensionUnit()) > $objDimension->getDimensionValue();
    }

    /**
     * Check if dimension on scale is equal to or more than given dimension
     *
     * @param   Measurable $objDimension
     * @return  bool
     */
    public function isEqualOrMoreThan(Measurable $objDimension)
    {
        return $this->amountIn($objDimension->getDimensionUnit()) >= $objDimension->getDimensionValue();
    }
}
