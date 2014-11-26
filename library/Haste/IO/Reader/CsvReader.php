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

class CsvReader implements \Iterator
{

    /**
     * Input file path
     * @var string
     */
    protected $strFilePath;

    /**
     * Input file
     * @var resource
     */
    protected $resFile;

    /**
     * Current row
     * @var array
     */
    protected $arrCurrent;

    /**
     * Iteration is valid
     * @var boolean
     */
    protected $blnValid;

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
     * Escape character
     * @var string
     */
    protected $strEscape = '\\';

    /**
     * Initialize the object
     * @param mixed
     */
    public function __construct($strFile)
    {
        if (!is_file(TL_ROOT . '/' . $strFile)) {
            throw new \InvalidArgumentException('Input file does not exist!');
        }

        $this->strFilePath = TL_ROOT . '/' . $strFile;
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
     * Set the enclosure character
     * @param string
     */
    public function setEnclosure($strEnclosure)
    {
        $this->strEnclosure = $strEnclosure;
    }

    /**
     * Set the escape character
     * @param string
     */
    public function setEscape($strEscape)
    {
        $this->strEscape = $strEscape;
    }

    /**
     * Return the current row of data
     * @return array|null
     */
    public function current()
    {
        return $this->arrCurrent;
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
        $this->arrCurrent = $this->readLine();

        if (!is_array($this->arrCurrent)) {
            $this->arrCurrent = null;
            $this->blnValid = false;
        }
    }

    /**
     * Reset the records
     */
    public function rewind()
    {
        // Lazy load
        if ($this->resFile === null) {
            $this->resFile = @fopen($this->strFilePath, 'r');
            $this->blnValid = ($this->resFile !== false);
            if ($this->valid()) {
                $this->arrCurrent = $this->readLine();
            }
        } else {
            $this->blnValid = true;
        }

        fseek($this->resFile, 0);
    }

    /**
     * Is row valid?
     * @return boolean
     */
    public function valid()
    {
        return $this->blnValid;
    }

    /**
     * Return a line
     * @return array|false
     */
    private function readLine()
    {
        return fgetcsv($this->resFile, 0, $this->strDelimiter, $this->strEnclosure, $this->strEscape);
    }
}