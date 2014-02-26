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

use Haste\File\CsvWriter\DataProvider\ProviderInterface;

class CsvWriter
{

    /**
     * Data provider
     * @var ProviderInterface
     */
    protected $objProvider;

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
    public function __construct(ProviderInterface $objProvider)
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
        $this->objProvider->rewind();

        while ($this->objProvider->valid()) {
            fputcsv($objFile->handle, $this->objProvider->current($varCallback), $this->strDelimiter, $this->strEnclosure);
            $this->objProvider->next();
        }

        return $objFile;
    }
}
