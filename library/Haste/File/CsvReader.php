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
     * File object
     * @var object
     */
    protected $objFile;

    /**
     * Target model
     * @var string
     */
    protected $strModel;

    /**
     * Fields mapper
     * @var array
     */
    protected $arrMapper = array();

    /**
     * Delimiter character
     * @var string
     */
    protected $strDelimiter = ';';

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
     * Header fields
     * @var boolean
     */
    protected $blnHeaderFields = false;

    /**
     * Initialize the object
     * @param mixed
     */
    public function __construct($varFile)
    {
        $this->setFile($varFile);
    }

    /**
     * Get an object property
     * @param string
     * @return mixed
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'file':
                return $this->objFile;

            case 'model':
                return $this->strModel;

            case 'table':
                $strModel = $this->strModel;
                return $strModel::getTable();

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
     * Set the file from string or object
     * @param mixed
     * @throws \InvalidArgumentException
     */
    public function setFile($varFile)
    {
        if (is_string($varFile)) {
            $varFile = new \File($varFile, true);
        }

        if (!is_object($varFile) || !$varFile->exists()) {
            throw new \InvalidArgumentException('Provided file does not exist!');
        }

        $this->objFile = $varFile;
    }

    /**
     * Set the mapper array
     * @param array
     * @throws \InvalidArgumentException
     */
    public function setMapper($arrMapper)
    {
        if (!is_array($arrMapper)) {
            throw new \InvalidArgumentException('Provided mapper is not an array');
        }

        $this->arrMapper = $arrMapper;
    }

    /**
     * Set the mapper array from DCA
     * @param string
     * @throws \Exception
     */
    public function setMapperFromDca($strTable)
    {
        if (!is_array($GLOBALS['TL_DCA'][$strTable]['fields'])) {
            throw new \Exception('There is no DCA loaded!');
        }

        $arrMapper = array();

        // Build the mapper
        foreach ($GLOBALS['TL_DCA']['tl_pensimo']['fields'] as $k => $v) {
            if (!is_array($v['eval']) || !array_key_exists('haste_csv_position', $v['eval'])) {
                continue;
            }

            $arrMapper[$k] = $v['eval']['haste_csv_position'];
        }

        $this->setMapper($arrMapper);
    }

    /**
     * Set the model name
     * @param string
     * @throws \InvalidArgumentException
     */
    public function setModel($strClass)
    {
        if (!class_exists($strClass)) {
            throw new \InvalidArgumentException(sprintf('Could not find the model class "%s"', $strClass));
        }

        $this->strModel = $strClass;
    }

    /**
     * Set the model from table name
     * @param string
     */
    public function setTable($strTable)
    {
        $this->setModel(\Model::getClassFromTable($strTable));
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
     * Set the header fields
     * @param boolean
     */
    public function setHeaderFields($blnHeaderFields)
    {
        $this->blnHeaderFields = (boolean) $blnHeaderFields;
    }

    /**
     * Save the data and return number of saved records
     * @param callable
     * @return integer
     */
    public function save($varCallback=null)
    {
        $time = time();
        $intIndex = 0;
        $intSaved = 0;
        $strModel = $this->strModel;
        $arrFields = \Database::getInstance()->getFieldNames($strModel::getTable());
        $arrFields = array_flip($arrFields);

        while (($arrData = fgetcsv($this->objFile->handle, 0, $this->strDelimiter, $this->strEnclosure, $this->strEscape)) !== false) {
            // Skip first row with header fields
            if (!$intIndex++ && $this->blnHeaderFields) {
                continue;
            }

            // Use mapper
            if (!empty($this->arrMapper)) {
                foreach ($this->arrMapper as $k => $v) {
                    if (!array_key_exists($v, $arrData)) {
                        continue;
                    }

                    $arrData[$k] = $arrData[$v];
                    unset($arrData[$v]);
                }
            }

            // Call the custom callback
            if (is_callable($varCallback)) {
                $arrData = call_user_func_array($varCallback, array($arrData, $intIndex));

                if ($arrData === false) {
                    continue;
                }
            }

            // Add the timestamp
            if (!isset($arrData['tstamp'])) {
                $arrData['tstamp'] = $time;
            }

            // Sort out the fields that are not present in database
            $arrData = array_intersect_key($arrData, $arrFields);

            if (empty($arrData)) {
                continue;
            }

            $objModel = new $strModel();
            $objModel->setRow($arrData);
            $objModel->save();

            // Increase the counter
            $intSaved++;
        }

        return $intSaved;
    }
}
