<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UndoEvent extends Event
{
    public const NAME = 'haste.undo';

    public function __construct(
        private array $hasteData,
        private int $id,
        private string $table,
        private array $row,
    ) {
    }

    public function getHasteData(): array
    {
        return $this->hasteData;
    }

    public function setHasteData(array $hasteData): self
    {
        $this->hasteData = $hasteData;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function getRow(): array
    {
        return $this->row;
    }

    public function setRow(array $row): self
    {
        $this->row = $row;

        return $this;
    }
}
