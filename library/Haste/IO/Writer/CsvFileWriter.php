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

namespace Haste\IO\Writer;

class CsvFileWriter extends AbstractFileWriter
{

    /**
     * Target file resource
     * @var resource
     */
    protected $resFile;

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
     * Construct csv writer
     * @param   string
     * @param   string
     */
    public function __construct($strFile = '', $strExtension = '.csv')
    {
        parent::__construct($strFile, $strExtension);
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
     * Prepare the file
     * @param   Traversable
     * @return  bool
     */
    protected function prepare(\Traversable $objReader)
    {
        if (!parent::prepare($objReader)) {
            return false;
        }

        $this->resFile = @fopen(TL_ROOT . '/' . $this->strFile, 'w');

        if (false === $this->resFile) {
            return false;
        }

        return true;
    }

    /**
     * Write row to CSV file
     * @param   array
     * @return  bool
     */
    protected function writeRow(array $arrData)
    {
        if (!is_array($arrData)) {
            return false;
        }

        return (bool) fputcsv($this->resFile, $arrData, $this->strDelimiter, $this->strEnclosure);
    }

    /**
     * Close file handle
     */
    protected function finish()
    {
        fclose($this->resFile);
    }
}
