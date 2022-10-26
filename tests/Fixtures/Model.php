<?php

namespace Codefog\Hastebundle\Tests\Fixtures;

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

    public static function findByPk(int $id): static
    {
        $model = new static();
        $model->id = $id;

        return $model;
    }
}
