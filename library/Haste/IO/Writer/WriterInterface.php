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

namespace Haste\IO\Writer;

use Haste\IO\Mapper\MapperInterface;

interface WriterInterface
{

    /**
     * Set mapper handler
     * @param MapperInterface|null $objMapper
     * @return  $this
     */
    public function setMapper(MapperInterface $objMapper = null);

    /**
     * Set row callback
     * @param   callable|null
     * @return  $this
     */
    public function setRowCallback($varRowCallback = null);

    /**
     * Write from the given data reader
     *
     * @param \Traversable $objReader Reader instance
     *
     * @return int the number of written rows
     */
    public function writeFrom(\Traversable $objReader);
}
