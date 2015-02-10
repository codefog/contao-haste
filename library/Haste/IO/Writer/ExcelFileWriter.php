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

class ExcelFileWriter extends AbstractFileWriter
{

    /**
     * Target file
     * @var string
     */
    protected $strFile;

    /**
     * PHPExcel instance
     * @var \PHPExcel
     */
    protected $objPHPExcel;

    /**
     * Current row in excel sheet
     * @var int
     */
    protected $currentRow;

    /**
     * Excel output format
     * @var string
     */
    protected $strFormat = 'Excel2007';

    /**
     * Construct csv writer
     * @param   string
     * @param   string
     */
    public function __construct($strFile = '', $strExtension = '.xlsx')
    {
        if (!class_exists('PHPExcel')) {
            throw new \LogicException('Please install "php_excel" extension before using '.__CLASS__);
        }

        parent::__construct($strFile, $strExtension);
    }

    /**
     * Set excel file format
     * @param   string
     * @return  $this
     */
    public function setFormat($strFormat)
    {
        switch ($strFormat) {
            case 'Excel5':
                $this->strExtension = '.xls';
                break;

            case 'Excel2007';
                $this->strExtension = '.xlsx';
                break;

            default:
                throw new \InvalidArgumentException('Invalid file format "' . $strFormat . '"');
        }

        $this->strFormat = (string) $strFormat;

        return $this;
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

        $this->currentRow = 0;
        $this->objPHPExcel = new \PHPExcel();

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->objPHPExcel->setActiveSheetIndex(0);

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

        $this->currentRow += 1;
        $currentColumn = 0;

        foreach ($arrData as $varValue) {
            $this->objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow(
                $currentColumn++,
                $this->currentRow,
                (string) $varValue,
                \PHPExcel_Cell_DataType::TYPE_STRING2
            );
        }

        return true;
    }

    /**
     * Write data to file
     */
    protected function finish()
    {
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, $this->strFormat);
        $objWriter->save(TL_ROOT . '/' . $this->strFile);
    }
}
