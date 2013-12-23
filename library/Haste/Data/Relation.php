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

use Haste\Generator\ModelData;


class Relation extends \ArrayObject
{

    public function __construct(\Model $objModel=null, $label='', array $additional=array(), $varCallable=null)
    {
        if ($label != '' && !isset($additional['label'])) {
            $additional['label'] = $label;
        }

        $values = array_merge(
            (array) ModelData::generate($objModel, $varCallable),
            $additional
        );

        parent::__construct($values, \ArrayObject::ARRAY_AS_PROPS);
    }

    public function __toString()
    {
        return (string) ($this->formatted ?: $this->id);
    }
}
