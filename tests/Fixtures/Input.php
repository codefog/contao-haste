<?php

declare(strict_types=1);

namespace Codefog\Hastebundle\Tests\Fixtures;

class Input
{
    private static array $data = [];

    public static function post(string $key): mixed
    {
        return self::$data[$key] ?? null;
    }

    public static function setPost(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }
}
