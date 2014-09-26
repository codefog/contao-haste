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

namespace Haste\Generator;

class RowClass
{

    /**
     * Class generator options
     */
    const NAME      = 1;
    const KEY       = 2;
    const COUNT     = 4;
    const EVENODD   = 8;
    const FIRSTLAST = 16;
    const ROW       = 32;
    const COL       = 64;


    protected $strKey;

    protected $intColumns = 0;

    protected $intOptions = 0;

    protected $strName;

    protected $strKeyPrefix;

    protected $strCountPrefix;

    protected $strEvenOddPrefix;

    protected $strFirstLastPrefix;

    protected $strRowPrefix;

    protected $strColPrefix;



    protected function __construct($strKey)
    {
        $this->strKey = $strKey;
    }

    public function addCustom($strName)
    {
        if ($strName == '') {
            throw new \InvalidArgumentException('Name is missing');
        }

        $this->strName = standardize((string) $strName);
        $this->intOptions = $this->intOptions | static::NAME;

        return $this;
    }

    public function addArrayKey($strPrefix='')
    {
        $this->strKeyPrefix = standardize((string) $strPrefix);
        $this->intOptions = $this->intOptions | static::KEY;

        return $this;
    }

    public function addCount($strPrefix)
    {
        if ($strPrefix == '') {
            throw new \InvalidArgumentException('Prefix is missing');
        }

        $this->strCountPrefix = standardize((string) $strPrefix);
        $this->intOptions = $this->intOptions | static::COUNT;

        return $this;
    }

    public function addEvenOdd($strPrefix='')
    {
        $this->strEvenOddPrefix = standardize((string) $strPrefix);
        $this->intOptions = $this->intOptions | static::EVENODD;

        return $this;
    }

    public function addFirstLast($strPrefix='')
    {
        $this->strFirstLastPrefix = standardize((string) $strPrefix);
        $this->intOptions = $this->intOptions | static::FIRSTLAST;

        return $this;
    }

    public function addGridRows($intPerColumn)
    {
        $this->intColumns = (int) $intPerColumn;
        $this->intOptions = $this->intOptions | static::ROW;

        return $this;
    }

    public function addGridCols($intPerColumn)
    {
        $this->intColumns = (int) $intPerColumn;
        $this->intOptions = $this->intOptions | static::COL;

        return $this;
    }


    /**
     * Generate row class for an array
     *
     * @param array $arrData data rows
     *
     * @return array
     */
    public function applyTo(array &$arrData)
    {
        $hasColumns = ($this->intColumns > 1);
        $total = count($arrData) - 1;
        $current = 0;
        $row = 0;
        $col = 0;
        $rows = 0;
        $cols = 0;

        if ($hasColumns)
        {
            $rows = ceil(count($arrData) / $this->intColumns) - 1;
            $cols = $this->intColumns - 1;
        }

        /** @type mixed $varValue */
        foreach ($arrData as $k => $varValue)
        {
            if ($hasColumns && $current > 0 && $current % $this->intColumns == 0)
            {
                ++$row;
                $col = 0;
            }

            // Increase total before generating class to prevent "last" on the first input field
            if (!$hasColumns && $varValue instanceof \FormPassword) {
                ++$total;
            }

            $class = $this->generateClass($current, $total, $k, $hasColumns, $row, $col, $rows, $cols);

            if (is_array($varValue))
            {
                $arrData[$k][$this->strKey] = trim($arrData[$k][$this->strKey] . $class);
            }
            elseif (is_object($varValue))
            {
                // Generate class on confirmation field
                if (!$hasColumns && $varValue instanceof \FormPassword) {
                    ++$current;
                    $varValue->rowClassConfirm = $this->generateClass($current, $total, $k);
                }

                $varValue->{$this->strKey} = trim($varValue->{$this->strKey} . $class);
                $arrData[$k] = $varValue;
            }
            else
            {
                $arrData[$k] = '<span class="' . trim($class) . '">' . $varValue . '</span>';
            }

            ++$col;
            ++$current;
        }

        return $this;
    }

    /**
     * Generate CSS class
     * @param   int
     * @param   int
     * @param   string
     * @param   bool
     * @param   int
     * @param   int
     * @param   int
     * @param   int
     * @return  string
     */
    protected function generateClass($current, $total, $k, $hasColumns=false, $row=0, $col=0, $rows=0, $cols=0)
    {
        $class = '';

        if ($this->intOptions & static::NAME)
        {
            $class .= ' ' . $this->strName;
        }

        if ($this->intOptions & static::KEY)
        {
            $class .= ' ' . $this->strKeyPrefix . $k;
        }

        if ($this->intOptions & static::COUNT)
        {
            $class .= ' ' . $this->strCountPrefix . $current;
        }

        if ($this->intOptions & static::EVENODD)
        {
            $class .= ' ' . $this->strEvenOddPrefix . ($current%2 ? 'odd' : 'even');
        }

        if ($this->intOptions & static::FIRSTLAST)
        {
            $class .= ($current == 0 ? ' ' . $this->strFirstLastPrefix . 'first' : '') . ($current == $total ? ' ' . $this->strFirstLastPrefix . 'last' : '');
        }

        if ($hasColumns && $this->intOptions & static::ROW)
        {
            $class .= ' row_'.$row . ($row%2 ? ' row_odd' : ' row_even') . ($row == 0 ? ' row_first' : '') . ($row == $rows ? ' row_last' : '');
        }

        if ($hasColumns && $this->intOptions & static::COL)
        {
            $class .= ' col_'.$col . ($col%2 ? ' col_odd' : ' col_even') . ($col == 0 ? ' col_first' : '') . ($col == $cols ? ' col_last' : '');
        }

        return $class;
    }


    public static function withKey($strKey)
    {
        if ($strKey == '') {
            throw new \InvalidArgumentException('Key is missing');
        }

        return new static($strKey);
    }
}
