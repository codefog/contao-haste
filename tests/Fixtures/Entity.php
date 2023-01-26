<?php

namespace Codefog\Hastebundle\Tests\Fixtures;

class Entity
{
    private ?string $pageTitle = null;
    private ?int $jumpTo = null;

    public function getPageTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function setPageTitle(?string $pageTitle): self
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    public function getJumpTo(): ?int
    {
        return $this->jumpTo;
    }

    public function setJumpTo(?int $jumpTo): self
    {
        $this->jumpTo = $jumpTo;

        return $this;
    }
}
