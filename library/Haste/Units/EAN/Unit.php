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
namespace Haste\Units\EAN;

/**
 * Class Unit
 */
class Unit
{
    /**
     * @const string
     */
    const E5 = 'E5';
    const EA = 'EA';
    const HE = 'HE';
    const HK = 'HK';
    const I6 = 'I6';
    const IC = 'IC';
    const IE = 'IE';
    const IK = 'IK';
    const SA = 'SA';
    const SG = 'SG';
    const UC = 'UC';
    const VC = 'VC';

    /**
     * @return string
     */
    public static function getBase()
    {
        return static::HE;
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        return array(
            static::E5,
            static::EA,
            static::HE,
            static::HK,
            static::I6,
            static::IC,
            static::IE,
            static::IK,
            static::SA,
            static::SG,
            static::UC,
            static::VC,
        );
    }
}
