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

namespace Haste\EventListener;

use Haste\Image\Image;

class TemplateHelpers
{
    /**
     * Magic __call method. Only here because stdClass supports direct __call()
     * only as of PHP7.
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        switch ($method) {
            case 'getGallery':
                return Image::getGallery($args[0]);
            case 'findImages':
                return Image::findImages($args[0]);
            case 'prepareImage':
                return Image::prepareImage($args[0], $args[1]);
            case 'sortImages':
                Image::sortImages($args[0], $args[1], $args[2]);
                return null;
        }

        throw new \InvalidArgumentException("$method is not a known Haste template helper, sorry!");
    }
}
