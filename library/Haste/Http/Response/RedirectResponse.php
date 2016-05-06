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


class RedirectResponse extends Response
{
    /**
     * Creates a new redirect HTTP response
     *
     * @param string $strTarget  The redirect target
     * @param int    $intStatus  The response HTTP status code
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($strTarget = '', $intStatus = 301)
    {
        parent::__construct('', $intStatus);

        $this->setHeader('Location', $strTarget);
    }
}
