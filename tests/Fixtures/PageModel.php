<?php

namespace Codefog\Hastebundle\Tests\Fixtures;

class PageModel extends \Contao\Model
{
    public static function getTable(): string
    {
        return 'tl_page';
    }
}
