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


class ZodiacSign
{

    /**
     * Day-of-the-year to zodiac sign map
     * @var array
     */
    protected $arrSigns = array(
        0       => 'capricorn',
        120     => 'aquarius',
        219     => 'pisces',
        321     => 'aries',
        420     => 'taurus',
        521     => 'gemini',
        621     => 'cancer',
        723     => 'leo',
        823     => 'virgo',
        923     => 'libra',
        1023    => 'scorpio',
        1122    => 'sagittarius',
        1222    => 'capricorn',
    );

    /**
     * Date
     * @var DateTime
     */
    protected $objDate;


    /**
     * Initialize Zodiac Sign object
     * @param   DateTime
     */
    public function __construct(\DateTime $objDate)
    {
        $this->objDate = $objDate;
    }

    /**
     * Get latin representation of zodiac sign
     * @return  string
     */
    public function getLatin()
    {
        $day = $this->objDate->format('nj');

        foreach (array_keys($this->arrSigns) as $k) {
            if ($k > $day) {
                break;
            }

            $key = $k;
        }

        return $this->arrSigns[$key];
    }

    /**
     * Get zodiac sign label
     * @return  string
     */
    public function getLabel()
    {
        \System::loadLanguageFile('zodiacsigns');

        $strKey = $this->getLatin();

        return $GLOBALS['TL_LANG']['ZODIACSIGN'][$strKey] ?: $strKey;
    }


    /**
     * Get zodiac sign from timestamp
     * @param   int
     * @return  string
     */
    public static function getLabelFromTimestamp($tstamp)
    {
        if ($tstamp == '') {
            return '';
        }

        $objSign = new ZodiacSign(DateTime::createFromFormat('U', $tstamp));

        return $objSign->getLabel();
    }
}
