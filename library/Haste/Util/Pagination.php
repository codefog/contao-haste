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
     * @var int
     */
    protected $total;

    /**
     * Items per page
     * @var int
     */
    protected $perPage;

    /**
     * URL parameter name
     * @var string
     */
    protected $urlParameter;

    /**
     * Limit
     * @var int
     */
    protected $limit = 0;

    /**
     * Offset
     * @var int
     */
    protected $offset = 0;

    /**
     * Max pagination links
     * @var int
     */
    protected $maxPaginationLinks;

    /**
     * The pagination is not valid if the page number is outside the range
     * @var bool
     */
    protected $isValid = false;

    /**
     * State
     * @var integer
     */
    protected $state = self::STATE_DIRTY;

    /**
     * Pagination object
     * @var \Pagination|null
     */
    protected $pagination;

    /**
     * Initialize the object
     *
     * @param int    $total
     * @param int    $perPage
     * @param string $urlParameter
     */
    public function __construct($total, $perPage, $urlParameter)
    {
        $this->setTotal($total);
        $this->setPerPage($perPage);
        $this->setUrlParameter($urlParameter);

        // Default values
        $this->setMaxPaginationLinks(\Config::get('maxPaginationLinks'));
    }

    /**
     * Gets the URL parameter
     *
     * @return string
     */
    public function getUrlParameter()
    {
        return $this->urlParameter;
    }

    /**
     * Set the URL parameter
     *
     * @param string $name
     *
     * @return $this
     */
    public function setUrlParameter($name)
    {
        $this->urlParameter = $name;

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
        return ($this->state === static::STATE_DIRTY);
    }

    /**
     * Gets the calculated limit
     *
     * @return int
     */
    public function getLimit()
    {
        $this->compile();

        return $this->limit;
    }

    /**
     * Gets the calculated offset
     *
     * @return int
     */
    public function getOffset()
    {
        $this->compile();

        return $this->offset;
    }

    /**
     * Gets the pagination object
     *
     * @return null|\Pagination
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * Gets maximum pagination links
     *
     * @return int
     */
    public function getMaxPaginationLinks()
    {
        return $this->maxPaginationLinks;
    }

    /**
     * Sets the maximum pagination links
     *
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
     * The pagination is not valid if the page number is outside the range
     *
     * @return bool
     */
    public function isValid()
    {
        $this->compile();

        return $this->isValid;
    }

    /**
     * Gets the number of rows per page
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Sets the number of rows per page
     *
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
     * Gets the total number of rows
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Sets the total number of rows
     *
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

        return $this->pagination->generate("\n  ");
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
            $page = \Input::get($this->getUrlParameter()) ?: 1;

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
            $pagination = new \Pagination($this->getTotal(), $this->getPerPage(), $this->getMaxPaginationLinks(), $this->getUrlParameter());
        }

        $this->state = self::STATE_CLEAN;
        $this->isValid = true;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->pagination = $pagination;
    }
}
