<?php

use Contao\CoreBundle\Twig\Runtime\BackendHelperRuntime;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())

    // Optional integrations
    ->ignoreErrorsOnPackage('terminal42/dc_multilingual', [ErrorType::DEV_DEPENDENCY_IN_PROD])

    ->ignoreUnknownClasses([BackendHelperRuntime::class, ClassMetadataInfo::class])
;
