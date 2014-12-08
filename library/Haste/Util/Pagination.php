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
     * State
     * @var integer
     */
    protected $state = self::STATE_DIRTY;

    /**
     * If the URL pagination parameter is out of range
     * @type bool
     */
    protected $outOfRange = false;

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
     * Max pagination links
     * @var int
     */
    protected $maxPaginationLinks;

    /**
     * Limit
     * @var int
     */
    protected $limit;

    /**
     * Offset
     * @var int
     */
    protected $offset;

    /**
     * Pagination object
     * @var \Pagination
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
     * Check if data is dirty (pagination needs to be generated)
     *
     * @return bool
     */
    public function isDirty()
    {
        return ($this->state === static::STATE_DIRTY);
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
     * Check if pagination URL parameter is out of range
     *
     * @return bool
     */
    public function isOutOfRange()
    {
        $this->compile();

        return $this->outOfRange;
    }

    /**
     * Gets the calculated limit
     *
     * @return int
     * @throws \OutOfRangeException
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
     * @throws \OutOfRangeException
     */
    public function getOffset()
    {
        $this->compile();

        return $this->offset;
    }

    /**
     * Gets the pagination object
     *
     * @return \Pagination
     * @throws \OutOfRangeException
     */
    public function getPagination()
    {
        $this->compile();

        return $this->pagination;
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

        $page = \Input::get($this->getUrlParameter()) ?: 1;

        // Set limit and offset
        $limit = $this->getPerPage() ?: $this->getTotal();
        $offset = (max($page, 1) - 1) * $this->getPerPage();

        // Overall limit
        if ($offset + $limit > $this->getTotal()) {
            $limit = $this->getTotal() - $offset;
        }

        $this->pagination = new \Pagination(
            $this->getTotal(),
            $this->getPerPage(),
            $this->getMaxPaginationLinks(),
            $this->getUrlParameter()
        );

        $this->state      = self::STATE_CLEAN;
        $this->limit      = $limit;
        $this->offset     = $offset;
        $this->outOfRange = false;

        // The pagination is not valid if the page number is outside the range
        if ($page < 1
            || ($this->getPerPage() == 0 && $page > 1)
            || ($this->getPerPage() > 0 && $page > max(ceil($this->getTotal() / $this->getPerPage()), 1))
        ) {
            $this->outOfRange = true;
        }
    }
}
