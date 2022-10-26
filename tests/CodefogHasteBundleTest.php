<?php

namespace Codefog\Hastebundle\Tests;

use Codefog\HasteBundle\CodefogHasteBundle;
use PHPUnit\Framework\TestCase;

class CodefogHasteBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new CodefogHasteBundle();

        $this->assertInstanceOf(CodefogHasteBundle::class, $bundle);
        $this->assertSame($bundle->getPath(), \dirname(__DIR__));
    }
}
