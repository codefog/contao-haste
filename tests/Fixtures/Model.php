<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Tests\Fixtures;

class Model
{
    private array $data = [];

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public static function findById(int $id): static
    {
        $model = new static();
        $model->id = $id;

        return $model;
    }
}
