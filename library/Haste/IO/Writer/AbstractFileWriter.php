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

abstract class AbstractFileWriter extends AbstractWriter
{

    /**
     * Target file
     * @var string
     */
    protected $strFile;

    /**
     * Generate random file name
     * @var bool
     */
    protected $blnRandomName = false;

    /**
     * File extension
     * @var string
     */
    protected $strExtension = '';

    /**
     * Construct file writer
     * @param   string
     * @param   string
     */
    public function __construct($strFile = '', $strExtension = '')
    {
        $strFile = (string) $strFile;

        if ($strFile == '') {
            $this->blnRandomName = true;
            $this->strExtension = $strExtension;
        } else {
            $this->strFile = $strFile;
        }
    }

    /**
     * Return file name
     * @return  string
     */
    public function getFilename()
    {
        return $this->strFile;
    }

    /**
     * Prepare the file
     * @param   Traversable
     * @return  bool
     */
    protected function prepare(\Traversable $objReader)
    {
        if ($this->blnRandomName) {
            $this->strFile = 'system/tmp/export_' . specialchars(uniqid()) . $this->strExtension;
        }

        return true;
    }
}
