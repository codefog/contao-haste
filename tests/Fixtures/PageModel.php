<?php

declare(strict_types=1);

namespace Codefog\Hastebundle\Tests\Fixtures;

use Contao\Model;

class PageModel extends Model
{
    public static function getTable(): string
    {
        return 'tl_page';
    }
}
