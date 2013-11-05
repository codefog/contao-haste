<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *
 * PHP version 5
 * @copyright  terminal42 gmbh 2013
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
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
