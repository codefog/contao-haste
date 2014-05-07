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

interface HeaderFieldsInterface
{

    /**
     * Has header fields
     * @return boolean
     */
    public function hasHeaderFields();

    /**
     * Get header fields
     * @return array
     */
    public function getHeaderFields();
}
