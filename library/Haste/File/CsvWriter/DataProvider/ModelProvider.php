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

class ModelProvider implements ProviderInterface
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
    public function __construct(\Model $objModel)
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
     * Get the next array of data
     * @param callable
     * @return array|boolean|null
     */
    public function getNext($varCallback=null)
    {
        if (!empty($this->arrHeaderFields) && !$this->blnHeaderFieldsUsed) {
            $this->blnHeaderFieldsUsed = true;
            return $this->arrHeaderFields;
        }

        $varData = $this->objModel->next();

        // Get the data as array
        if ($varData !== false) {
            $varData = $this->objModel->row();
        }

        if (is_callable($varCallback)) {
            $varData = call_user_func_array($varCallback, array($varData, $this->objModel));
        }

        // Skip records if the returned data is null
        if ($varData === null) {
            $varData = $this->getNext($varCallback);
        }

        return $varData;
    }
}
