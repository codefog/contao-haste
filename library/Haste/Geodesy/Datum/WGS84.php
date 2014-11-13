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


class WGS84 implements GeodeticDatum
{

    /**
     * Latitude
     * @var string
     */
    protected $lat;

    /**
     * Longitude
     * @var string
     */
    protected $lng;


    public function __construct($lat, $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * Get latitude
     * @return  string
     */
    public function getLatitude()
    {
        return $this->lat;
    }

    /**
     * Get longitude
     * @return  string
     */
    public function getLongitude()
    {
        return $this->lng;
    }

    /**
     * Get datum in WGS84 format (worldwide standard)
     * @return  WGS84
     */
    public function getAsWGS84()
    {
        return $this;
    }

    /**
     * Find coordinates using the google maps geocode service
     *
     * @param string $strStreet
     * @param string $strPostal
     * @param string $strCity
     * @param string $strCountry
     *
     * @return WGS84|null
     */
    public static function findAddressOnGoogleMaps($strStreet, $strPostal, $strCity, $strCountry)
    {
        $strAddress = sprintf('%s, %s %s %s', $strStreet, $strPostal, $strCity, $strCountry);
        $strAddress = urlencode($strAddress);

        // Get the coordinates
        $objRequest = new \Request();
        $objRequest->send('http://maps.googleapis.com/maps/api/geocode/json?address=' . $strAddress . '&sensor=false');

        // Request failed
        if ($objRequest->hasError()) {
            \System::log('Could not get coordinates for: ' . $strAddress . ' (' . $objRequest->response . ')', __METHOD__, TL_ERROR);

            return null;
        }

        $objResponse = json_decode($objRequest->response);

        return new static($objResponse->results[0]->geometry->location->lat, $objResponse->results[0]->geometry->location->lng);
    }

    /**
     * Create datum from WGS84 format
     * @param   \Haste\Geodesy\Datum\WGS84
     * @return  GeodeticDatum
     */
    public static function createFromWGS84(WGS84 $objDatum)
    {
        return $objDatum;
    }
}
