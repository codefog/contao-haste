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

namespace Haste\File\CsvWriter;

use Haste\File\CsvWriter\DataProvider;

class CsvWriter
{

    /**
     * Data provider
     * @var ProviderInterface
     */
    protected $objProvider;

    /**
     * Use header fields
     * @var boolean
     */
    protected $blnHeaderFields = false;

    /**
     * Delimiter character
     * @var string
     */
    protected $strDelimiter = ',';

    /**
     * Enclosure character
     * @var string
     */
    protected $strEnclosure = '"';

    /**
     * Initialize object
     * @param ProviderInterface
     */
    public function __construct(\Traversable $objProvider)
    {
        $this->objProvider = $objProvider;
    }

    /**
     * Return the delimiter character
     * @return string
     */
    public function getDelimiter()
    {
        return $this->strDelimiter;
    }

    /**
     * Set the delimiter character
     * @param string
     */
    public function setDelimiter($strDelimiter)
    {
        $this->strDelimiter = $strDelimiter;
    }

    /**
     * Return the enclosure character
     * @return string
     */
    public function getEnclosure()
    {
        return $this->strEnclosure;
    }

    /**
     * Set the enclosure character
     * @param string
     */
    public function setEnclosure($strEnclosure)
    {
        $this->strEnclosure = $strEnclosure;
    }

    /**
     * Enable the header fields
     */
    public function enableHeaderFields()
    {
        if ($this->objProvider instanceof DataProvider\HeaderFieldsInterface) {
            $this->blnHeaderFields = $this->objProvider->hasHeaderFields();
        }
    }

    /**
     * Disable the header fields
     */
    public function disableHeaderFields()
    {
        $this->blnHeaderFields = false;
    }

    /**
     * Save the data to CSV file
     * @param string
     * @param callable
     * @return object
     */
    public function save($strFile='', $varCallback=null)
    {
        return $this->createFile($varCallback, $strFile);
    }

    /**
     * Download the CSV file
     * @param string
     * @param callable
     */
    public function download($strFile='', $varCallback=null)
    {
        $objFile = $this->createFile($varCallback, $strFile);
        $objFile->sendToBrowser();
    }

    /**
     * Create the file
     * @param callable
     * @param string
     * @return object
     */
    protected function createFile($varCallback=null, $strFile='')
    {
        if (!$strFile) {
            $strFile = 'system/tmp/export_' . md5(uniqid('', true)) . '.csv';
        }

        $objFile = new \File($strFile);
        $objFile->truncate();

        // Add header fields
        if ($this->blnHeaderFields) {
            $arrHeaderFields = $this->objProvider->getHeaderFields();

            if (is_array($arrHeaderFields)) {
                fputcsv($objFile->handle, $arrHeaderFields, $this->strDelimiter, $this->strEnclosure);
            }
        }

        foreach ($this->objProvider as $arrData) {
            if (is_callable($varCallback)) {
                $arrData = call_user_func($varCallback, $arrData);
            }

            if (!is_array($arrData)) {
                continue;
            }

            fputcsv($objFile->handle, $arrData, $this->strDelimiter, $this->strEnclosure);
        }

        return $objFile;
    }
}
