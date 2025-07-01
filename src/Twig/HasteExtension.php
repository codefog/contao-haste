<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Twig;

use Codefog\HasteBundle\Formatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HasteExtension extends AbstractExtension
{
    public function __construct(private readonly Formatter $formatter)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dca_label', $this->formatter->dcaLabel(...)),
            new TwigFunction('dca_value', $this->formatter->dcaValue(...)),
        ];
    }
}
