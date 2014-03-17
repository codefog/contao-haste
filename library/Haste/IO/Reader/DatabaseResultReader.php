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

class DatabaseResultReader implements HeaderFieldsInterface, \Iterator
{

    /**
     * Database result
     * @var object
     */
    protected $objResult;

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
    public function __construct(\Database\Result $objResult)
    {
        $this->objResult = $objResult;
        $this->blnValid = ($this->objResult->numRows > 0);
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
     * @return array|null
     */
    public function current()
    {
        return $this->objResult->row();
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
        if (!$this->objResult->next()) {
            $this->blnValid = false;
        }
    }

    /**
     * Reset the records
     */
    public function rewind()
    {
        $this->objResult->reset();
        $this->blnValid = ($this->objResult->count() > 0);
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
