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
namespace Haste\Units\Volume;

/**
 * Class Dimension
 */
class Volume implements Measurable
{
    /**
     * Volume amount
     * @param float
     */
    protected $fltValue;

    /**
     * Volume unit
     * @param string
     */
    protected $strUnit;

    /**
     * Volume constructor.
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
    public function getVolumeValue()
    {
        return $this->fltValue;
    }

    /**
     * @param bool $ISO
     * @return string
     */
    public function getVolumeUnit($ISO = false)
    {
        return $ISO ? Unit::$arrISO[$this->strUnit] : $this->strUnit;
    }

    /**
     * Create volume object from timePeriod widget value
     * @param mixed
     * @return Volume|null
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
