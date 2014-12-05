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
     * Is generated
     *
     * @var bool
     */
    protected $isGenerated = false;

    /**
     * Is valid
     *
     * @var bool
     */
    protected $isValid = false;

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
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return bool
     */
    public function applies()
    {
        return ($this->getLimit() || $this->getOffset()) ? true : false;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        $this->generate();

        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        $this->generate();

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
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
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
     */
    public function setMaxPaginationLinks($maxPaginationLinks)
    {
        $this->maxPaginationLinks = $maxPaginationLinks;
    }

    /**
     * @return boolean
     */
    public function isGenerated()
    {
        return $this->isGenerated;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        $this->generate();

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
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
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
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * Add the pagination to template
     *
     * @param \Template $template
     * @param $key
     */
    public function addToTemplate(\Template $template, $key = null)
    {
        $this->addToObject($template, $key);
    }

    /**
     * Add the pagination to object
     *
     * @param $object
     * @param $key
     */
    public function addToObject($object, $key = null)
    {
        $this->generate();

        // Set the default key
        if ($key === null) {
            $key = 'pagination';
        }

        if ($this->isValid() && $this->pagination !== null) {
            $object->$key = $this->pagination->generate($this->getSeparator());
        }
    }

    /**
     * Generate the pagination
     *
     * @param integer
     * @param integer
     * @param string
     * @param object
     */
    public function generate()
    {
        if ($this->isGenerated()) {
            return;
        }

        $this->isGenerated = true;

        $limit = 0;
        $offset = 0;
        $pagination = null;

        if ($this->getPerPage() > 0) {
            $page = \Input::get($this->getKey()) ?: 1;

            // The pagination is not valid if the page number is outside the range
            if ($page < 1 || $page > max(ceil($this->getTotal() / $this->getPerPage()), 1)) {
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

        $this->isValid = true;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->pagination = $pagination;
    }
}
