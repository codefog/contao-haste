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

class ModelCollectionProvider implements HeaderFieldsInterface, \Iterator
{

    /**
     * Model
     * @var object
     */
    protected $objModel;

    /**
     * Iteration is valid
     * @var boolean
     */
    protected $blnValid = true;

    /**
     * Header fields
     * @var array
     */
    protected $arrHeaderFields = array();

    /**
     * Initialize the object
     * @param object
     */
    public function __construct(\Model\Collection $objModel)
    {
        $this->objModel = $objModel;
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
        return $this->objModel->row();
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
        if (!$this->objModel->next()) {
            $this->blnValid = false;
        }
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
        return $this->blnValid;
    }
}
