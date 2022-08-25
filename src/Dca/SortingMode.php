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

namespace Codefog\HasteBundle\Dca;


/**
 * Class SortingMode for sorting modes in DCA files
 *
 * @package Codefog\HasteBundle\Util
 * @see https://docs.contao.org/books/api/dca/reference.html#sorting
 */
class SortingMode
{

    /**
     * Records are not sorted
     */
    const NONE = 0;

    /**
     * Records are sorted by a fixed field
     */
    const FIXED_FIELD = 1;

    /**
     * Records are sorted by a switchable field
     */
    const SWITCHABLE_FIELD = 2;

    /**
     * Records are sorted by the parent table
     */
    const PARENT_TABLE = 3;

    /**
     * Displays the child records of a parent record (see style sheets module)
     */
    const CHILD_VIEW = 4;

    /**
     * Records are displayed as tree (see site structure)
     */
    const TREE_VIEW = 5;

    /**
     * Displays the child records within a tree structure (see articles module)
     */
    const CHILD_TREE_VIEW = 6;

}
