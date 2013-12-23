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

namespace Haste\Data;

use Haste\Util\Format;


class Timestamp extends Plain
{

    public function __construct($value, $label='', array $additional=array())
    {
        $additional = array_merge(
            array(
                'date'  => Format::date($value),
                'datim' => Format::datim($value),
                'time'  => Format::time($value)
            ),
            $additional
        );

        parent::__construct($value, $label, $additional);
    }
}
