<?php

namespace Codefog\HasteBundle\Util;

class ArrayPosition
{
    public const FIRST = 0;
    public const LAST = 1;
    public const BEFORE = 2;
    public const AFTER = 3;

    protected int $position;
    protected string $fieldName;

    public function __construct(int $position, string $fieldName = null)
    {
        switch ($position) {
            case static::FIRST:
            case static::LAST:
                $this->position = $position;
                break;

            case static::BEFORE:
            case static::AFTER:
                if (!$fieldName) {
                    throw new \LogicException('Missing field name for before/after position.');
                }

                $this->position = $position;
                $this->fieldName = $fieldName;
                break;

            default:
                throw new \InvalidArgumentException('Invalid position "' . $position . '"');
        }
    }

    public function position()
    {
        return $this->position;
    }

    public function fieldName()
    {
        return $this->fieldName;
    }

    public function addToArray(array $existing, array $new): array
    {
        switch ($this->position) {

            case static::FIRST:
                $existing = array_merge($new, $existing);
                break;

            case static::LAST;
                $existing = array_merge($existing, $new);
                break;

            case static::BEFORE;
            case static::AFTER;
                if (!isset($existing[$this->fieldName])) {
                    throw new \LogicException('Index "' . $this->fieldName . '" does not exist in array');
                }

                $keys = array_keys($existing);
                $pos = array_search($this->fieldName, $keys) + (int) ($this->position === static::AFTER);

                $arrBuffer = array_splice($existing, 0, $pos);
                $existing = array_merge($arrBuffer, $new, $existing);
                break;
        }

        return $existing;
    }

    public static function first(): static
    {
        return new static(static::FIRST);
    }

    public static function last(): static
    {
        return new static(static::LAST);
    }

    public static function before($fieldName): static
    {
        return new static(static::BEFORE, $fieldName);
    }

    public static function after($fieldName): static
    {
        return new static(static::AFTER, $fieldName);
    }
}
