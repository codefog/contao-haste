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

class ModelCollectionReader implements HeaderFieldsInterface, \Iterator
{

    /**
     * Model Collection
     * @var object
     */
    protected $objCollection;

    /**
     * Iteration is valid
     * @var boolean
     */
    protected $blnValid;

    /**
     * Header fields
     * @var array
     */
    protected $arrHeaderFields = array();

    /**
     * Initialize the object
     * @param object
     */
    public function __construct(\Model\Collection $objCollection)
    {
        $this->objCollection = $objCollection;
        $this->blnValid = ($this->objCollection->numRows > 0);
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
     * Return the current row of data
     * @return array
     */
    public function current()
    {
        return $this->objCollection->row();
    }

    /**
     * Return the current key
     * @return null
     */
    public function key()
    {
        return null;
    }

    /**
     * Get the next position
     */
    public function next()
    {
        if (!$this->objCollection->next()) {
            $this->blnValid = false;
        }
    }

    /**
     * Reset the records
     */
    public function rewind()
    {
        $this->objCollection->reset();
        $this->blnValid = ($this->objCollection->count() > 0);

    }

    /**
     * Is row valid?
     * @return boolean
     */
    public function valid()
    {
        return $this->blnValid;
    }
}
