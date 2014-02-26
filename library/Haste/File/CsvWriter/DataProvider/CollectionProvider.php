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

namespace Haste\File\CsvWriter\DataProvider;

class CollectionProvider implements \Iterator
{

    /**
     * Model
     * @var object
     */
    protected $objModel;

    /**
     * Header fields
     * @var array
     */
    protected $arrHeaderFields = array();

    /**
     * Were the header fields already used?
     * @var boolean
     */
    protected $blnHeaderFieldsUsed = false;

    /**
     * Initialize the object
     * @param object
     */
    public function __construct(\Collection $objModel)
    {
        $this->objModel = $objModel;
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
     * @param callable
     * @return array|null
     */
    public function current($varCallback=null)
    {
        if (!empty($this->arrHeaderFields) && !$this->blnHeaderFieldsUsed) {
            $this->blnHeaderFieldsUsed = true;
            return $this->arrHeaderFields;
        }

        $varData = null;

        // Get the data as array
        if ($this->objModel !== null) {
            $varData = $this->objModel->row();
        }

        if (is_callable($varCallback)) {
            $varData = call_user_func($varCallback, $varData, $this->objModel);
        }

        // Skip records if the returned data is null
        if ($varData === false) {
            $this->next();
            $varData = $this->current($varCallback);
        }

        return $varData;
    }

    /**
     * Return the current key
     * @return boolean
     */
    public function key()
    {
        return false;
    }

    /**
     * Get the next position
     */
    public function next()
    {
        $this->objModel->next();
    }

    /**
     * Reset the records
     */
    public function rewind()
    {
        $this->objModel->reset();
    }

    /**
     * Is row valid?
     * @return boolean
     */
    public function valid()
    {
        return $this->current() !== null;
    }
}
