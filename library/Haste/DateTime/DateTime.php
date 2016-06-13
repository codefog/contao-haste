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
     *
     * @param \DateTime|null $objDiff
     *
     * @return int
     */
    public function getAge(\DateTime $objDiff = null)
    {
        if ($objDiff === null) {
            $objDiff = new \DateTime();
        }

        return $this->diff($objDiff)->y;
    }

    /**
     * Create an instance of Haste\DateTime\DateTime from given format.
     *
     * @param string             $format
     * @param string             $time
     * @param \DateTimeZone|null $object
     *
     * @return static
     */
    public static function createFromFormat($format, $time, $object = null)
    {
        if (null === $object) {
            $native = \DateTime::createFromFormat($format, $time);
        } else {
            $native = \DateTime::createFromFormat($format, $time, $object);
        }

        // \DateTime::createFromFormat might fail and return false
        if (!$native instanceof \DateTime) {
            return $native;
        }

        $date = new static();
        $date->setTimezone($native->getTimezone());
        $date->setTimestamp($native->getTimestamp());

        return $date;
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
