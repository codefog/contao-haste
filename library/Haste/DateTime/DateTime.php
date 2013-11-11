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

namespace Haste\DateTime;

class DateTime extends \DateTime
{

    /**
     * Calculate age from timestamp
     * @return  int
     */
    public function getAge(\DateTime $objDiff=null)
    {
        if ($objDiff === null) {
            $objDiff = new \DateTime();
        }

        return $this->diff($objDiff)->y;
    }

    /**
     * Create new DateTime object from timestamp
     * @param   int
     * @param   DateTimeZone
     */
    public static function createFromTimestamp($tstamp, \DateTimeZone $timezone=null)
    {
        return static::createFromFormat('U', $tstamp, $timezone);
    }
}
