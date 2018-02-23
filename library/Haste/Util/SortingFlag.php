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
 * Class SortingFlag for sorting flags in DCA files
 *
 * @package Haste\Util
 * @see https://docs.contao.org/books/api/dca/reference.html#sorting
 */
class SortingFlag
{

    /**
     * Sort by initial letter ascending
     */
    const INITIAL_LETTER_ASC = 1;

    /**
     * Sort by initial letter descending
     */
    const INITIAL_LETTER_DESC = 2;

    /**
     * Sort by initial two letters ascending
     */
    const INITIAL_TWO_LETTERS_ASC = 3;

    /**
     * Sort by initial two letters descending
     */
    const INITIAL_TWO_LETTERS_DESC = 4;

    /**
     * Sort by day ascending
     */
    const DAY_ASC = 5;

    /**
     * Sort by day descending
     */
    const DAY_DESC = 6;

    /**
     * Sort by month ascending
     */
    const MONTH_ASC = 7;

    /**
     * Sort by month descending
     */
    const MONTH_DESC = 8;

    /**
     * Sort by year ascending
     */
    const YEAR_ASC = 9;

    /**
     * Sort by year descending
     */
    const YEAR_DESC = 10;

    /**
     * Sort ascending
     */
    const ASC = 11;

    /**
     * Sort descending
     */
    const DESC = 12;

}
