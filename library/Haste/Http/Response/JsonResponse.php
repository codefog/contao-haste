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

namespace Haste\Http\Response;

class JsonResponse extends Response
{
    /**
     * Creates a new JSON encoded HTTP response
     *
     * @param mixed $varContent The response content as string or array
     * @param int   $intStatus  The response HTTP status code
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($varContent = '', $intStatus = 200)
    {
        parent::__construct('', $intStatus);

        $this->setContent($varContent);
        $this->setHeader('Content-Type', 'application/json');
    }

    /**
     * Prepares the content
     * @param   mixed
     * @param   integer
     * @param   integer
     */
    public function setContent($varContent, $intOptions = 0, $intDepth = 512)
    {
        // Depth parameter is only supported from PHP 5.5
        if (version_compare(PHP_VERSION, '5.5', '>=')) {
            $strContent = json_encode($varContent, $intOptions, $intDepth);
        } else {
            $strContent = json_encode($varContent, $intOptions);
        }

        parent::setContent($strContent);
    }
}
