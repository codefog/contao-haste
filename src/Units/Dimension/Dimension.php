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
namespace Codefog\HasteBundle\Units\Dimension;

/**
 * Class Dimension
 */
class Dimension implements Measurable
{
    /**
     * Dimension amount
     * @param   float
     */
    protected $fltValue;

    /**
     * Dimension unit
     * @param   string
     */
    protected $strUnit;

    /**
     * Dimension constructor.
     *
     * @param $fltValue
     * @param $strUnit
     */
    public function __construct($fltValue, $strUnit)
    {
        $this->fltValue = $fltValue;
        $this->strUnit = (string) $strUnit;
    }

    /**
     * @return float
     */
    public function getDimensionValue()
    {
        return $this->fltValue;
    }

    /**
     * @param bool $ISO
     * @return string
     */
    public function getDimensionUnit($ISO = false)
    {
        return $ISO ? Unit::$arrISO[$this->strUnit] : $this->strUnit;
    }

    /**
     * Create dimension object from timePeriod widget value
     * @param   mixed
     * @return  Dimension|null
     */
    public static function createFromTimePeriod($arrData)
    {
        $arrData = deserialize($arrData);

        if (empty($arrData)
            || !is_array($arrData)
            || $arrData['value'] === ''
            || $arrData['unit'] === ''
            || !in_array($arrData['unit'], Unit::getAll())) {
            return null;
        }

        return new static($arrData['value'], $arrData['unit']);
    }
}
