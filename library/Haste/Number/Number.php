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

namespace Haste\Number;

/**
 * This class is inspired by the Money library of Mathias Verraes
 */
class Number
{
    /**
     * The amount in integer representation
     * @var int
     */
    private $intAmount;

    /**
     * Create a Number instance
     *
     * @param int $intAmount The amount in representation to support 4 floating points
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($intAmount)
    {
        if (!is_int($intAmount)) {
            throw new \InvalidArgumentException("The first parameter of Number must be an integer.");
        }

        $this->intAmount = $intAmount;
    }

    /**
     * Gets the amount
     * @return  int
     */
    public function getAmount()
    {
        return $this->intAmount;
    }

    /**
     * Adds up another Number instance
     *
     * @param \Haste\Number\Number $objToAdd
     *
     * @return \Haste\Number\Number
     */
    public function add(\Haste\Number\Number $objToAdd)
    {
        return new self($this->getAmount() + $objToAdd->getAmount());
    }

    /**
     * Subtracts another Number instance
     *
     * @param \Haste\Number\Number $objToSubstract
     *
     * @return \Haste\Number\Number
     */
    public function subtract(\Haste\Number\Number $objToSubstract)
    {
        return new self($this->getAmount() - $objToSubstract->getAmount());
    }

    /**
     * Multiplies with another Number instance
     *
     * @param \Haste\Number\Number $objToMultiplyWith
     *
     * @return \Haste\Number\Number
     */
    public function multiply(\Haste\Number\Number $objToMultiplyWith)
    {
        return new self((int) $this->getAmount() * $objToMultiplyWith->getAmount() / 10000);
    }

    /**
     * Divides by another Number instance
     *
     * @param \Haste\Number\Number $objToDivideBy
     *
     * @return \Haste\Number\Number
     */
    public function divide(\Haste\Number\Number $objToDivideBy)
    {
        return new self((int) (($this->getAmount() * 10000) / $objToDivideBy->getAmount()));
    }

    /**
     * Check if two Number instances are equal
     *
     * @param \Haste\Number\Number
     *
     * @return bool
     */
    public function equals(\Haste\Number\Number $objToCompare)
    {
        return $this->getAmount() === $objToCompare->getAmount();
    }

    /**
     * Check if greater than another Number instance
     *
     * @param \Haste\Number\Number
     *
     * @return bool
     */
    public function greaterThan(\Haste\Number\Number $objToCompare)
    {
        return $this->getAmount() > $objToCompare->getAmount();
    }

    /**
     * Check if less than another Number instance
     *
     * @param \Haste\Number\Number
     *
     * @return bool
     */
    public function lessThan(\Haste\Number\Number $objToCompare)
    {
        return $this->getAmount() < $objToCompare->getAmount();
    }

    /**
     * Check if zero
     *
     * @return boolean
     */
    public function isZero()
    {
        return $this->getAmount() === 0;
    }

    /**
     * Check if positive
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->getAmount() > 0;
    }

    /**
     * Check if negative
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->getAmount() < 0;
    }

    /**
     * Get the float value
     *
     * @return float
     */
    public function getAsFloat()
    {
        return (float) $this->getAsString();
    }

    /**
     * Get the string value
     *
     * @return string
     */
    public function getAsString()
    {
        $strFirst = (string) substr($this->getAmount(), 0, -4);

        if ($strFirst === '' || $strFirst === '-') {
            $strFirst = '0';
        }

        if ($this->isNegative()) {
            $strFirst = '-' . $strFirst;
        }

        $strSecond = str_pad(substr(($this->getAmount() * ($this->isNegative() ? -1 : 1)), -4), 4, '0', STR_PAD_LEFT);
        $strSecond = rtrim($strSecond, '0');

        if ($strSecond === '') {
            return (string) $strFirst;
        }

        return (string)  $strFirst . '.' . $strSecond;
    }

    /**
     * Echoes the correct value
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getAsString();
    }

    /**
     * Create Number instance from PHP value
     *
     * @param mixed
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function create($varInput)
    {
        if (is_float($varInput) || is_int($varInput)) {
            $varInput = (string) $varInput;
        }

        if (!is_string($varInput)) {
            throw new \InvalidArgumentException('Input must be float or integer or string.');
        }

        if (preg_match('/(.+?)([.,](\d+))?$/', $varInput, $arrMatches) === false) {
            throw new \InvalidArgumentException('Input is not a valid number representation.');
        }

        $strValue = str_replace(array('.', ',', '\''), '', $arrMatches[1]);
        $strDecimals = $arrMatches[3];

        if (!is_numeric($strValue)) {
            throw new \InvalidArgumentException('Input is not a valid number representation.');
        }

        return new static((int) ($strValue . str_pad(substr($strDecimals, 0, 4), 4, '0', STR_PAD_RIGHT)));
    }
}
