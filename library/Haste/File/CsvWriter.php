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

class CsvWriter
{

    /**
     * Source model
     * @var object
     */
    protected $strModel;

    /**
     * Fields mapper
     * @var array
     */
    protected $arrMapper = array();

    /**
     * Header fields
     * @var array
     */
    protected $arrHeaderFields = array();

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
     * Get an object property
     * @param string
     * @return mixed
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'model':
                return $this->objModel;

            case 'table':
                if (!$this->objModel) {
                    return '';
                }

                return $this->objModel->getTable();

            case 'mapper':
                return $this->arrMapper;

            case 'headerFields':
                return $this->arrHeaderFields;

            case 'delimiter':
                return $this->strDelimiter;

            case 'enclosure':
                return $this->strEnclosure;

            default:
                return null;
        }
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
     * Set the header fields array
     * @param array
     * @throws \InvalidArgumentException
     */
    public function setHeaderFields($arrHeaderFields)
    {
        if (!is_array($arrHeaderFields)) {
            throw new \InvalidArgumentException('Provided header fields is not an array');
        }

        $this->arrHeaderFields = $arrHeaderFields;
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
        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $k => $v) {
            if (!is_array($v['eval']) || !array_key_exists('haste_csv_position', $v['eval'])) {
                continue;
            }

            $arrMapper[$k] = $v['eval']['haste_csv_position'];
        }

        $this->setMapper($arrMapper);
    }

    /**
     * Set the model name
     * @param object
     * @throws \InvalidArgumentException
     */
    public function setModel($objModel)
    {
        $this->objModel = $objModel;
    }

    /**
     * Set the model from table name
     * @param string
     */
    public function setTable($strTable)
    {
        $strClass = \Model::getClassFromTable($strTable);

        if (!class_exists($strClass)) {
            throw new \InvalidArgumentException('Provided table does not have a model assigned');
        }

        $this->objModel = $strClass::findAll();
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
     * @param callable
     * @param string
     */
    public function download($varCallback=null, $strFile='')
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

        // Add the header fields
        if (!empty($this->arrHeaderFields)) {
            fputcsv($objFile->handle, $this->arrHeaderFields, $this->strDelimiter, $this->strEnclosure);
        }

        if ($objModel !== null) {
            while ($objModel->next()) {
                $arrData = $objModel->row();

                // Use mapper
                if (!empty($this->arrMapper)) {
                    foreach ($this->arrMapper as $k => $v) {
                        if (array_key_exists($k, $arrData)) {
                            continue;
                        }

                        $arrData[$v] = $arrData[$k];
                        unset($arrData[$k]);
                    }
                }

                // Call the custom callback
                if (is_callable($varCallback)) {
                    $arrData = call_user_func_array($varCallback, array($arrData));

                    if ($arrData === false) {
                        continue;
                    }
                }

                // Clean array of string keys
                foreach ($arrData as $k => $v) {
                    if (is_string($k)) {
                        unset($arrData[$k]);
                    }
                }

                fputcsv($objFile->handle, $arrData, $this->strDelimiter, $this->strEnclosure);
            }
        }

        return $objFile;
    }
}
