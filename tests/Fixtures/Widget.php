<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests\Fixtures;

class Widget
{
    public string $name;

    public mixed $value = null;

    public function __construct(array $attributes)
    {
        $this->name = $attributes['name'];
        $this->value = $attributes['value'];
    }

    public function submitInput(): bool
    {
        return true;
    }

    public function hasErrors(): bool
    {
        return false;
    }

    public static function getAttributesFromDca(array $data, string $name, mixed $value = null): array
    {
        $data['name'] = $name;
        $data['type'] = $data['inputType'];
        $data['value'] = $value;

        return $data;
    }
}
