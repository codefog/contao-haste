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

namespace Haste\File;

class CsvReader
{

    /**
     * CSV data
     * @var array
     */
    protected $arrData = array();

    /**
     * Fields mapper
     * @var array
     */
    protected $arrMapper = array();

    /**
     * Delimiter
     * @var string
     */
    protected $strDelimiter = ';';

    /**
     * Enclosure
     * @var string
     */
    protected $strEnclosure = '"';

    /**
     * Escape
     * @var string
     */
    protected $strEscape = '\\';

    /**
     * Header fields
     * @param boolean
     */
    protected $blnHeaderFields = false;

    /**
     * Set an object property
     * @param string
     * @param mixed
     * @throws \Exception
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'data':
                $this->arrData = (array) $varValue;
                break;

            case 'mapper':
                $this->arrMapper = (array) $varValue;
                break;

            case 'delimiter':
                $this->strDelimiter = $varValue;
                break;

            case 'enclosure':
                $this->strEnclosure = $varValue;
                break;

            case 'escape':
                $this->strEscape = $varValue;
                break;

            case 'headerFields':
                $this->blnHeaderFields = (boolean) $varValue;
                break;

            default:
                throw new \Exception(sprintf('Trying to set invalid "%s" object property', $strKey));
        }
    }

    /**
     * Get an object property
     * @param string
     * @return mixed
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'data':
                return $this->arrData;

            case 'mapper':
                return $this->arrMapper;

            case 'delimiter':
                return $this->strDelimiter;

            case 'enclosure':
                return $this->strEnclosure;

            case 'escape':
                return $this->strEscape;

            case 'headerFields':
                return $this->blnHeaderFields;

            default:
                return null;
        }
    }

    /**
     * Set the data from file
     * @param string
     * @param integer
     * @throws \Exception
     */
    public function setFromFile($strFile, $intLength=0)
    {
        if (!is_file(TL_ROOT . '/' . $strFile)) {
            throw new \Exception(sprintf('File "%s" does not exist!', $strFile));
        }

        $resFile = @fopen(TL_ROOT . '/' . $strFile, 'r');

        if ($resFile === false) {
            throw new \Exception(sprintf('Could not open "%s" file!', $strFile));
        }

        while (($arrData = fgetcsv($resFile, $intLength, $this->strDelimiter, $this->strEnclosure, $this->strEscape)) !== false) {
            $this->arrData[] = $arrData;
        }
    }

    /**
     * Set the data from string
     * @param mixed
     * @param string
     * @throws \Exception
     * @see http://www.php.net/manual/en/function.str-getcsv.php#101888
     */
    public function setFromString($strData, $strBreak="\n")
    {
        $this->arrData = str_getcsv($strData, $strBreak);

        foreach ($this->arrData as $k => $v) {
            $this->arrData[$k] = str_getcsv($v, $this->strDelimiter, $this->strEnclosure, $this->strEscape);
        }
    }

    /**
     * Save the data to table
     * @param string
     * @param callable
     * @throws \Exception
     */
    public function saveToTable($strTable, $varCallback=null)
    {
        $strClass = \Model::getClassFromTable($strTable);

        if (!class_exists($strClass)) {
            throw new \Exception(sprintf('Could not find the model class for "%s"', $strTable));
        }

        $arrFields = \Database::getInstance()->getFieldNames($strTable);
        $arrFields = array_flip($arrFields);

        foreach ($this->arrData as $intIndex => $arrData) {
            // Skip first row with header fields
            if (!$intIndex && $this->blnHeaderFields) {
                continue;
            }

            // Use mapper
            if (!empty($this->arrMapper)) {
                foreach ($this->arrMapper as $k => $v) {
                    if (!array_key_exists($k, $arrData)) {
                        continue;
                    }

                    $arrData[$v] = $arrData[$k];
                    unset($arrData[$k]);
                }
            }

            // Call the custom callback
            if (is_callable($varCallback)) {
                $arrData = call_user_func_array($varCallback, array($arrData, $intIndex));

                if ($arrData === false) {
                    continue;
                }
            }

            // Sort out the fields that are not present in database
            $arrData = array_intersect_key($arrData, $arrFields);

            $objModel = new $strClass();
            $objModel->setRow($arrData);
            $objModel->save();
        }
    }
}
