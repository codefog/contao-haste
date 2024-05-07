<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests\Fixtures;

class Entity
{
    private string|null $pageTitle = null;

    private int|null $jumpTo = null;

    public function getPageTitle(): string|null
    {
        return $this->pageTitle;
    }

    public function setPageTitle(string|null $pageTitle): self
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    public function getJumpTo(): int|null
    {
        return $this->jumpTo;
    }

    public function setJumpTo(int|null $jumpTo): self
    {
        $this->jumpTo = $jumpTo;

        return $this;
    }
}
