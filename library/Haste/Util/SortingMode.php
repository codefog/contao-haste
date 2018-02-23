<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2018 Tastaturberuf <code@tastaturberuf.de>
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Util;


/**
 * Class SortingMode for sorting modes in DCA files
 *
 * @package Haste\Util
 * @see https://docs.contao.org/books/api/dca/reference.html#sorting
 */
class SortingMode
{

    /**
     * Records are not sorted
     */
    const NOT_SORTED = 0;

    /**
     * Records are sorted by a fixed field
     */
    const SORTED_FIXED_FIELD = 1;

    /**
     * Records are sorted by a switchable field
     */
    const SORTED_SWITCHABLE_FIELD = 2;

    /**
     * Records are sorted by the parent table
     */
    const SORTED_PARENT_TABLE = 3;

    /**
     * Displays the child records of a parent record (see style sheets module)
     */
    const DISPLAY_AS_CHILD = 4;

    /**
     * Records are displayed as tree (see site structure)
     */
    const DISPLAY_AS_TREE = 5;

    /**
     * Displays the child records within a tree structure (see articles module)
     */
    const DISPLAY_AS_CHILD_TREE = 6;

}
