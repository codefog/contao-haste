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

namespace Haste\IO\Mapper;

class ArrayMapper implements MapperInterface
{

    /**
     * Mapper config
     * @var array
     */
    protected $arrMap;

    /**
     * Preserve unmapped fields
     * @var bool
     */
    protected $blnPreserveUnmapped = true;


    /**
     * Construct mapper from array
     * @param   array
     */
    public function __construct(array $arrMap)
    {
        $this->arrMap = $arrMap;
    }

    /**
     * Set if to preserve unmapped fields
     * @param   bool
     * @return  $this
     */
    public function setPreserveUnmapped($blnValue)
    {
        $this->blnPreserveUnmapped = (bool) $blnValue;

        return $this;
    }

    /**
     * Get if to preserve unmapped fields
     * @return  bool
     */
    public function getPreserveUnmapped()
    {
        return $this->blnPreserveUnmapped;
    }

    /**
     * Map row
     * @param   array
     * @return  array
     */
    public function map(array $arrRow)
    {
        $arrData = array();

        foreach ($this->arrMap as $k => $v) {
            $arrData[$v] = $arrRow[$k];
            unset($arrRow[$k]);
        }

        if ($this->blnPreserveUnmapped) {
            $arrData = array_merge($arrRow, $arrData);
        }

        return $arrData;
    }

    /**
     * Returns the map
     * @return array
     */
    public function getMap()
    {
        return $this->arrMap;
    }
}
