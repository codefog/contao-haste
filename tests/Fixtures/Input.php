<?php

namespace Codefog\Hastebundle\Tests\Fixtures;

class Input
{
    private static array $data = [];

    public static function post(string $key): mixed
    {
        return static::$data[$key] ?? null;
    }

    public static function setPost(string $key, mixed $value): void
    {
        static::$data[$key] = $value;
    }
}
