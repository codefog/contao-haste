<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__.'/tools/ecs/vendor/contao/easy-coding-standard/config/self.php');

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::SKIP, [
        HeaderCommentFixer::class => null,
    ]);
};
