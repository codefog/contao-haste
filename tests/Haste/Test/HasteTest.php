<?php
/**
 * Created by PhpStorm.
 * User: yanickwitschi
 * Date: 23/11/13
 * Time: 14:31
 */

namespace Haste\Test;

use Haste\Haste;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/../../../../../../library/Haste.php';

class HasteTest extends TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf('\Haste\Haste', Haste::getInstance());
    }

    public function testCall()
    {
        $this->assertSame(\Controller::replaceInsertTags('foobar'), 'foobar');
        $this->assertSame(\Controller::generateFrontendUrl('foobar'), 'foobar');
    }
} 