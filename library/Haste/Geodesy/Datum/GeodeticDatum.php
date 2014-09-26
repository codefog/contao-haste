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


namespace Haste\Geodesy\Datum;


interface GeodeticDatum
{

    /**
     * Get datum in WGS84 format (worldwide standard)
     * @return  WGS84
     */
    public function getAsWGS84();

    /**
     * Create datum from WGS84 format
     * @param   WGS84
     * @return  GeodeticDatum
     */
    public static function createFromWGS84(WGS84 $objDatum);
}
