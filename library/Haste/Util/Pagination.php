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

namespace Haste\Util;

class Pagination
{

    /**
     * State of the pagination
     * Can be either clean or dirty
     */
    const STATE_CLEAN = 0;
    const STATE_DIRTY = 1;

    /**
     * Total items
     *
     * @var integer
     */
    protected $total;

    /**
     * Items per page
     *
     * @var integer
     */
    protected $perPage;

    /**
     * URL parameter name
     *
     * @var string
     */
    protected $key;

    /**
     * Separator
     *
     * @var string
     */
    protected $separator;

    /**
     * Limit
     *
     * @var integer
     */
    protected $limit = 0;

    /**
     * Offset
     *
     * @var integer
     */
    protected $offset = 0;

    /**
     * Max pagination links
     *
     * @var integer
     */
    protected $maxPaginationLinks;

    /**
     * Is valid
     *
     * @var bool
     */
    protected $isValid = false;

    /**
     * State
     *
     * @var integer
     */
    protected $state = self::STATE_DIRTY;

    /**
     * Pagination object
     *
     * @var \Pagination|null
     */
    protected $pagination;

    /**
     * Initialize the object
     *
     * @param integer
     * @param integer
     * @param string
     */
    public function __construct($total, $perPage, $key)
    {
        $this->setTotal($total);
        $this->setPerPage($perPage);
        $this->setKey($key);

        // Default values
        $this->setSeparator("\n  ");
        $this->setMaxPaginationLinks(\Config::get('maxPaginationLinks'));
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return bool
     */
    public function applies()
    {
        return ($this->getLimit() || $this->getOffset()) ? true : false;
    }

    /**
     * Check if data is dirty (pagination needs to be generated)
     *
     * @return bool
     */
    public function isDirty()
    {
        return (bool) ($this->state === static::STATE_DIRTY);
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        $this->compile();

        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        $this->compile();

        return $this->offset;
    }

    /**
     * @return null|\Pagination
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @param string $separator
     *
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->state = self::STATE_DIRTY;
        $this->separator = $separator;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxPaginationLinks()
    {
        return $this->maxPaginationLinks;
    }

    /**
     * @param int $maxPaginationLinks
     *
     * @return $this
     */
    public function setMaxPaginationLinks($maxPaginationLinks)
    {
        $this->state = self::STATE_DIRTY;
        $this->maxPaginationLinks = $maxPaginationLinks;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        $this->compile();

        return $this->isValid;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param int $perPage
     *
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->state = self::STATE_DIRTY;
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->state = self::STATE_DIRTY;
        $this->total = $total;

        return $this;
    }

    /**
     * Generate the pagination and return it as HTML string
     *
     * @return string
     */
    public function generate()
    {
        $this->compile();

        return $this->pagination->generate($this->getSeparator());
    }

    /**
     * Compile the pagination
     */
    protected function compile()
    {
        if (!$this->isDirty()) {
            return;
        }

        $limit = 0;
        $offset = 0;
        $pagination = null;

        if ($this->getPerPage() > 0) {
            $page = \Input::get($this->getKey()) ?: 1;

            // The pagination is not valid if the page number is outside the range
            if ($page < 1 || $page > max(ceil($this->getTotal() / $this->getPerPage()), 1)) {
                $this->state = self::STATE_CLEAN;
                $this->isValid = false;
                return;
            }

            // Set limit and offset
            $limit = $this->getPerPage();
            $offset += (max($page, 1) - 1) * $this->getPerPage();

            // Overall limit
            if ($offset + $limit > $this->getTotal()) {
                $limit = $this->getTotal() - $offset;
            }

            // Add the pagination menu
            $pagination = new \Pagination($this->getTotal(), $this->getPerPage(), $this->getMaxPaginationLinks(), $this->getKey());
        }

        $this->state = self::STATE_CLEAN;
        $this->isValid = true;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->pagination = $pagination;
    }

    /**
     * Generate a pagination and return it as HTML string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->generate();
    }
}
