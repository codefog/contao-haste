<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests\Fixtures;

class DataContainer
{
    private array $keys = [];

    public function __construct()
    {
    }

    public function __set($strKey, $varValue): void
    {
        $this->keys[$strKey] = $varValue;
    }

    public function __get($strKey): mixed
    {
        return $this->keys[$strKey] ?? null;
    }
}
