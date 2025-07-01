<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests;

use Codefog\HasteBundle\CodefogHasteBundle;
use PHPUnit\Framework\TestCase;

class CodefogHasteBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new CodefogHasteBundle();

        $this->assertSame($bundle->getPath(), \dirname(__DIR__));
    }
}
