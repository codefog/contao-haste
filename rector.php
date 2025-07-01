<?php

declare(strict_types=1);

use Contao\Rector\Rector\ContainerSessionToRequestStackSessionRector;
use Contao\Rector\Rector\LegacyFrameworkCallToServiceCallRector;
use Contao\Rector\Set\ContaoSetList;
use Contao\Rector\ValueObject\LegacyFrameworkCallToServiceCall;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Bundle210\Rector\Class_\EventSubscriberInterfaceToAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->skip([
        EventSubscriberInterfaceToAttributeRector::class,
    ]);
};
