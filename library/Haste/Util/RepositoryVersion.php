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

namespace Haste\Util;

class RepositoryVersion
{
    /**
     * Array with status detail names.
     */
    private static $mStatusName = array(
        'alpha1', 'alpha2', 'alpha3',
        'beta1', 'beta2', 'beta3',
        'RC1', 'RC2', 'RC3',
        'stable'
    );

    private static $mShortStatusName = array(
        '&#945;1', '&#945;2', '&#945;3',
        '&#946;1', '&#946;2', '&#946;3',
        'r1', 'r2', 'r3',
        'st'
    );

    /**
     * Format a version number to human readable with long status text
     *
     * Example:
     * <code>
     * echo Format::version(10030042);
     * // will output: 1.3.4 alpha3
     * </code>
     *
     * @param int $version The encoded version
     *
     * @return string The version in human readable format
     */
    public static function format($version)
    {
        $version    = (int) $version;

        if (!$version) {
            return '';
        }

        $status     = $version % 10;
        $version   = (int) ($version / 10);
        $micro      = $version % 1000;
        $version   = (int) ($version / 1000);
        $minor      = $version % 1000;
        $major      = (int) ($version / 1000);

        return "$major.$minor.$micro " . static::$mStatusName[$status];
    }

    /**
     * Format a version number to human readable with short status text
     *
     * Example:
     * <code>
     * echo Format::shortVersion(10030042);
     * // will output: 1.3.4 a3
     * </code>
     *
     * @param int $version The encoded version
     *
     * @return string  The version in human readable format
     */
    public static function formatShort($version)
    {
        $version    = (int) $version;

        if (!$version) {
            return '';
        }

        $status     = $version % 10;
        $version   = (int) ($version / 10);
        $micro      = $version % 1000;
        $version   = (int) ($version / 1000);
        $minor      = $version % 1000;
        $major      = (int) ($version / 1000);

        return $status < 9 ? "$major.$minor.$micro ".static::$mShortStatusName[$status] : "$major.$minor.$micro";
    }

    /**
     * Encode version from human readable format.
     *
     * Example:
     * <code>
     * echo Repository::encodeVersion('2.9.21 beta2');
     * // will output: 20090214
     * </code>
     *
     * @param string $version Human readable representation of a version.
     *
     * @return int The encoded version number
     */
    public static function encode($version)
    {
        $matches = array();

        if (preg_match('/(\d{1,3})\.(\d{1,3})\.(\d{1,3})([ \-](\w+))?/', $version, $matches)) {
            $stat = strtolower($matches[5]);
            $v = array_search($stat, array_map('strtolower', static::$mStatusName));

            if ($v === false) {
                $v = 9;
            }

            return (($matches[1] * 1000 + $matches[2]) * 1000 + $matches[3]) * 10 + $v;

        } elseif (preg_match('/(\d{1,3})\.(\d{1,3})\.(\w*)/', $version, $matches)) {
            $stat = strtolower($matches[3]);
            $v = array_search($stat, array_map('strtolower', static::$mStatusName));

            if ($v === false) {
                $v = 9;
            }

            return (($matches[1] * 1000 + $matches[2]) * 1000) * 10 + $v;
        }

        return 0;
    }
}
