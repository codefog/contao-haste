<?php

namespace Codefog\HasteBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CodefogHasteBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
