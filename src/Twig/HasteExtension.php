<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Twig;

use Codefog\HasteBundle\Formatter;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\Runtime\BackendHelperRuntime;
use Contao\Image;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HasteExtension extends AbstractExtension
{
    public function __construct(private readonly Formatter $formatter)
    {
    }

    public function getFunctions(): array
    {
        $functions = [
            new TwigFunction('dca_label', $this->formatter->dcaLabel(...)),
            new TwigFunction('dca_value', $this->formatter->dcaValue(...)),
        ];

        if (!class_exists(BackendHelperRuntime::class)) {
            $functions[] = new TwigFunction(
                'backend_icon',
                $this->generateIcon(...),
                ['is_safe' => ['html']],
            );
        }

        return $functions;
    }

    private function generateIcon(string $src, string $alt = '', HtmlAttributes|null $attributes = null): string
    {
        return Image::getHtml($src, $alt, $attributes ? $attributes->toString(false) : '');
    }
}
