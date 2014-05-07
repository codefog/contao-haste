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

use Haste\IO\Mapper\MapperInterface;
use Haste\IO\Reader\HeaderFieldsInterface;

abstract class AbstractWriter implements WriterInterface
{

    /**
     * Mapper object
     * @var object
     */
    protected $objMapper;

    /**
     * Callback or closure for every row
     * @var callable
     */
    protected $varRowCallback;

    /**
     * Use header fields
     * @var boolean
     */
    protected $blnHeaderFields = false;


    /**
     * Enable the header fields
     */
    public function enableHeaderFields()
    {
        $this->blnHeaderFields = true;
    }

    /**
     * Disable the header fields
     */
    public function disableHeaderFields()
    {
        $this->blnHeaderFields = false;
    }

    /**
     * Set mapper handler
     * @param   MapperInterface|null
     * @return  $this
     */
    public function setMapper(MapperInterface $objMapper = null)
    {
        $this->objMapper = $objMapper;

        return $this;
    }

    /**
     * Set row callback
     * @param   callable|null
     * @return  $this
     */
    public function setRowCallback($varRowCallback = null)
    {
        $this->varRowCallback = $varRowCallback;

        return $this;
    }

    /**
     * Write from the given data reader
     * @param   Traversable
     * @return  int             the number of written rows
     */
    public function writeFrom(\Traversable $objReader)
    {
        if (!$this->prepare($objReader)) {
            return false;
        }

        $intWritten = 0;

        // Add header fields
        if ($this->blnHeaderFields && $objReader instanceof HeaderFieldsInterface && $objReader->hasHeaderFields()) {
            if ($this->writeRow($objReader->getHeaderFields())) {
                $intWritten += 1;
            }
        }

        foreach ($objReader as $arrRow) {
            if ($this->objMapper instanceof MapperInterface) {
                $arrRow = $this->objMapper->map($arrRow);
            }

            if (null !== $this->varRowCallback) {
                $arrRow = call_user_func($this->varRowCallback, $arrRow);

                if ($arrRow === false) {
                    continue;
                }
            }

            if ($this->writeRow($arrRow)) {
                $intWritten += 1;
            }
        }

        $this->finish();

        return $intWritten;
    }

    /**
     * Prepare writer
     * @param   Traversable
     * @return  bool
     */
    abstract protected function prepare(\Traversable $objReader);

    /**
     * Write row
     * @param   array
     * @return  bool
     */
    abstract protected function writeRow(array $arrRow);

    /**
     * Finish writing
     */
    abstract protected function finish();
}
