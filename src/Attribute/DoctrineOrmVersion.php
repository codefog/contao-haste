<?php

namespace Codefog\HasteBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class DoctrineOrmVersion
{
    public function __construct(public string $editRouteName = 'contao_backend', public array $editRouteParams = [])
    {
    }
}
