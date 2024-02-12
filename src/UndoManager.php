<?php

declare(strict_types=1);

namespace Codefog\HasteBundle;

use Codefog\HasteBundle\Event\UndoEvent;
use Contao\Backend;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UndoManager
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Undo the record manually triggered in the backend.
     */
    public function onUndoCallback(DataContainer $dc): void
    {
        $this->undo((int) $dc->id, $dc);

        Backend::redirect(System::getReferer());
    }

    /**
     * Return the "undo" button.
     */
    public function button(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        if ($this->hasData((int) $row['id'])) {
            $href = '&amp;key=haste_undo';
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Add additional data to the tl_undo table record.
     */
    public function add(int $undoId, string $key, mixed $data): bool
    {
        $undoData = $this->connection->fetchOne('SELECT haste_data FROM tl_undo WHERE id=?', [$undoId]);

        if (false === $undoData) {
            return false;
        }

        $undoData = $undoData ? json_decode((string) $undoData, true) : [];
        $undoData[$key] = $data;

        $affectedRows = $this->connection->update('tl_undo', ['haste_data' => json_encode($undoData)], ['id' => $undoId]);

        return $affectedRows > 0;
    }

    /**
     * Undo the record.
     */
    public function undo(int $undoId, DataContainer|null $dc = null): bool
    {
        $record = $this->connection->fetchAssociative('SELECT * FROM tl_undo WHERE id=?', [$undoId]);
        $data = StringUtil::deserialize($record['data']);

        if (!\is_array($data)) {
            return false;
        }

        $error = false;
        $fieldsMapper = [];
        $hasteData = json_decode((string) $record['haste_data'], true);
        $schemaManager = $this->connection->createSchemaManager();

        // Restore the data
        foreach ($data as $table => $fields) {
            // Get the currently available fields
            if (!isset($fieldsMapper[$table])) {
                $fieldsMapper[$table] = array_keys($schemaManager->listTableColumns($table));
            }

            foreach ($fields as $row) {
                // Unset fields that no longer exist in the database
                foreach (array_keys($row) as $field) {
                    if (!\in_array(strtolower($field), $fieldsMapper[$table], true)) {
                        unset($row[$field]);
                    }
                }

                // Re-insert the data
                $affectedRows = $this->connection->insert($table, $row);

                // Do not delete record from tl_undo if there is an error
                if ($affectedRows < 1) {
                    $error = true;
                    continue;
                }

                $insertId = $this->connection->lastInsertId();
                Controller::loadDataContainer($table);

                // Trigger the undo_callback
                if (\is_array($GLOBALS['TL_DCA'][$table]['config']['onundo_callback'] ?? null)) {
                    foreach ($GLOBALS['TL_DCA'][$table]['config']['onundo_callback'] as $callback) {
                        if (\is_array($callback)) {
                            System::importStatic($callback[0])->{$callback[1]}($table, $row, $dc);
                        } elseif (\is_callable($callback)) {
                            $callback($table, $row, $dc);
                        }
                    }
                }

                $this->eventDispatcher->dispatch(new UndoEvent($hasteData, (int) $insertId, $table, $row));
            }
        }

        // Delete record from tl_undo if there was no error
        if (!$error) {
            $this->connection->delete('tl_undo', ['id' => $undoId]);
        }

        return !$error;
    }

    /**
     * Check if the record has data to undo.
     */
    public function hasData(int $undoId): bool
    {
        $undoData = $this->connection->fetchOne('SELECT haste_data FROM tl_undo WHERE id=? LIMIT 1', [$undoId]);

        if (!$undoData) {
            return false;
        }

        $undoData = json_decode((string) $undoData, true);

        return \is_array($undoData) && \count($undoData) > 0;
    }
}
