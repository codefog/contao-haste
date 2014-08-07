<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2013 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Util;

class ArrayPosition
{

    const FIRST = 0;
    const LAST = 1;
    const BEFORE = 2;
    const AFTER = 3;

    protected $position;
    protected $fieldName;

    public function __construct($position, $fieldName = null)
    {
        switch ($position) {

            case static::FIRST:
            case static::LAST:
                $this->position = $position;
                break;

            case static::BEFORE:
            case static::AFTER:
                if ($fieldName == '') {
                    throw new \LogicException('Missing field name for before/after position.');
                }

                $this->position = $position;
                $this->fieldName = $fieldName;
                break;

            default:
                throw new \InvalidArgumentException('Invalid position "' . $position . '"');
        }
    }

    public function position()
    {
        return $this->position;
    }

    public function fieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param array $arrValues
     * @param array $arrNew
     * @return array
     */
    public function addToArray(array $arrValues, array $arrNew)
    {
        switch ($this->position) {

            case static::FIRST:
                $arrValues = array_merge($arrNew, $arrValues);
                break;

            case static::LAST;
                $arrValues = array_merge($arrValues, $arrNew);
                break;

            case static::BEFORE;
            case static::AFTER;
                if (!isset($arrValues[$this->fieldName])) {
                    throw new \LogicException('Index "' . $this->fieldName . '" does not exist in array');
                }

                $keys = array_keys($arrValues);
                $pos = array_search($this->fieldName, $keys) + (int) ($this->position == static::AFTER);

                $arrBuffer = array_splice($arrValues, 0, $pos);
                $arrValues = array_merge($arrBuffer, $arrNew, $arrValues);
                break;
        }

        return $arrValues;
    }

    public static function first()
    {
        return new static(static::FIRST);
    }

    public static function last()
    {
        return new static(static::LAST);
    }

    public static function before($fieldName)
    {
        return new static(static::BEFORE, $fieldName);
    }

    public static function after($fieldName)
    {
        return new static(static::AFTER, $fieldName);
    }
}