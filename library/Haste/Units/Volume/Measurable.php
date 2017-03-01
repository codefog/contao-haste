<?php
/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2017 Codefog & terminal42 gmbh & RAD Consulting GmbH
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */
namespace Haste\Units\Volume;

/**
 * Interface Measurable
 */
interface Measurable
{
    /**
     * Get the volume amount based on dimension unit
     *
     * @return float
     */
    public function getVolumeValue();

    /**
     * Get the volume unit
     *
     * @param bool $ISO
     * @return string
     */
    public function getVolumeUnit($ISO = false);
}
