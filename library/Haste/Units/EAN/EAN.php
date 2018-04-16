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
namespace Haste\Units\EAN;

/**
 * Class EAN
 */
class EAN
{
    /**
     * @var float
     */
    protected $value;

    /**
     * @var string
     */
    protected $unit;

    /**
     * @param float  $value
     * @param string $unit
     */
    public function __construct($value, $unit)
    {
        $this->value = $value;
        $this->unit = (string)$unit;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param mixed $data
     * @return EAN|null
     */
    public static function createFromTimePeriod($data)
    {
        $data = deserialize($data);

        if (empty($data) || !is_array($data) || $data['value'] === '' || $data['unit'] === '' || !in_array($data['unit'], Unit::getAll())) {
            return new static(0, Unit::getBase());
        }

        return new static($data['value'], $data['unit']);
    }
}
