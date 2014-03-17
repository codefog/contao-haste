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

namespace Haste\IO\Reader;

class ArrayReader implements HeaderFieldsInterface, \IteratorAggregate
{

    /**
     * Array data
     * @var array
     */
    protected $arrData = array();

    /**
     * Header fields
     * @var array
     */
    protected $arrHeaderFields = array();

    /**
     * Initialize the object
     * @param array
     */
    public function __construct(array $arrData)
    {
        $this->arrData = $arrData;
    }

    /**
     * Has header fields
     * @return boolean
     */
    public function hasHeaderFields()
    {
        return !empty($this->arrHeaderFields);
    }

    /**
     * Get header fields
     * @return array
     */
    public function getHeaderFields()
    {
        return $this->arrHeaderFields;
    }

    /**
     * Set header fields
     * @param array
     */
    public function setHeaderFields(array $arrHeaderFields)
    {
        $this->arrHeaderFields = $arrHeaderFields;
    }

    /**
     * Return the iterator
     * @return array
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->arrData);
    }
}
