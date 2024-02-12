<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Util;

use Contao\Config;
use Contao\Input;

class Pagination
{
    public const STATE_CLEAN = 0;

    public const STATE_DIRTY = 1;

    protected int $currentState = self::STATE_DIRTY;

    protected bool $outOfRange = false;

    protected int $total;

    protected int $perPage;

    protected string $urlParameter;

    protected int $maxPaginationLinks;

    protected int $limit;

    protected int $offset;

    protected \Contao\Pagination $pagination;

    public function __construct(int $total, int $perPage, string $urlParameter)
    {
        $this->setTotal($total);
        $this->setPerPage($perPage);
        $this->setUrlParameter($urlParameter);
        $this->setMaxPaginationLinks(Config::get('maxPaginationLinks'));
    }

    public function getCurrentState(): int
    {
        return $this->currentState;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->currentState = self::STATE_DIRTY;
        $this->total = $total;

        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $perPage): self
    {
        $this->currentState = self::STATE_DIRTY;
        $this->perPage = $perPage;

        return $this;
    }

    public function getUrlParameter(): string
    {
        return $this->urlParameter;
    }

    public function setUrlParameter(string $urlParameter): self
    {
        $this->currentState = self::STATE_DIRTY;
        $this->urlParameter = $urlParameter;

        return $this;
    }

    public function getMaxPaginationLinks(): int
    {
        return $this->maxPaginationLinks;
    }

    public function setMaxPaginationLinks(int $maxPaginationLinks): self
    {
        $this->currentState = self::STATE_DIRTY;
        $this->maxPaginationLinks = $maxPaginationLinks;

        return $this;
    }

    public function isOutOfRange(): bool
    {
        $this->compile();

        return $this->outOfRange;
    }

    public function getLimit(): int
    {
        $this->compile();

        return $this->limit;
    }

    public function getOffset(): int
    {
        $this->compile();

        return $this->offset;
    }

    public function getPagination(): \Contao\Pagination
    {
        $this->compile();

        return $this->pagination;
    }

    /**
     * Generate the pagination and return it as HTML string.
     */
    public function generate(): string
    {
        $this->compile();

        return $this->pagination->generate("\n  ");
    }

    /**
     * Compile the pagination.
     */
    protected function compile(): void
    {
        if (self::STATE_CLEAN === $this->getCurrentState()) {
            return;
        }

        $page = Input::get($this->getUrlParameter()) ?: 1;

        // Set limit and offset
        $limit = $this->getPerPage() ?: $this->getTotal();
        $offset = (max($page, 1) - 1) * $this->getPerPage();

        // Overall limit
        if ($offset + $limit > $this->getTotal()) {
            $limit = $this->getTotal() - $offset;
        }

        $this->pagination = new \Contao\Pagination(
            $this->getTotal(),
            $this->getPerPage(),
            $this->getMaxPaginationLinks(),
            $this->getUrlParameter(),
        );

        $this->currentState = self::STATE_CLEAN;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->outOfRange = false;

        // The pagination is not valid if the page number is outside the range
        if (
            $page < 1
            || (0 === $this->getPerPage() && $page > 1)
            || ($this->getPerPage() > 0 && $page > max(ceil($this->getTotal() / $this->getPerPage()), 1))
        ) {
            $this->outOfRange = true;
        }
    }
}
