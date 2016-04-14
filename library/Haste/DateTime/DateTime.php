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
     *
     * @param int                $tstamp
     * @param \DateTimeZone|null $timezone
     *
     * @return static
     *
     * @deprecated Deprecated since Haste 4.12, to be removed in 5.0. Use new DateTime('@' . $time) instead.
     */
    public static function createFromTimestamp($tstamp, \DateTimeZone $timezone = null)
    {
        if (null !== $timezone) {
            trigger_error(
                'Passing a timezone when creating DateTime from timestamp is not supported by PHP.',
                E_USER_DEPRECATED
            );
        }

        return new static('@'.$tstamp);
    }
}
