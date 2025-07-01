<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Model;

use Codefog\HasteBundle\DcaRelationsManager;
use Contao\Model;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;

abstract class DcaRelationsModel extends Model
{
    public function __set($key, $value): void
    {
        if (($this->arrRelations[$key]['type'] ?? null) === 'haste-ManyToMany' && !\is_array($value)) {
            throw new \InvalidArgumentException('Values set on many-to-many relation fields have to be an array');
        }

        parent::__set($key, $value);
    }

    public function getRelated($key, array $options = [])
    {
        try {
            $relation = static::getRelation(static::getTable(), $key);
        } catch (\InvalidArgumentException) {
            $relation = null;
        }

        if (null !== $relation) {
            $strClass = static::getClassFromTable($relation['related_table']);

            if (class_exists($strClass)) {
                /** @var Connection $connection */
                $connection = System::getContainer()->get('database_connection');

                $ids = $connection->fetchFirstColumn('SELECT '.$relation['related_field'].' FROM '.$relation['table'].' WHERE '.$relation['reference_field'].'=?', [$this->{$relation['reference']}]);

                if (0 === \count($ids)) {
                    return null;
                }

                $collection = [];

                // Fetch from registry first (only possible if no options and the relation field
                // is the PK)
                if (0 === \count($options) && $relation['field'] === $strClass::getPk()) {
                    foreach ($ids as $k => $id) {
                        $model = Registry::getInstance()->fetch($relation['related_table'], $id);

                        if (null !== $model) {
                            unset($ids[$k]);
                        }

                        $collection[$id] = $model;
                    }
                }

                // Fetch remaining
                if (\count($ids) > 0) {
                    $remainingModels = $strClass::findBy([$relation['related_table'].'.'.$relation['field']." IN('".implode("','", $ids)."')"], null, $options);

                    foreach ($remainingModels as $remaining) {
                        $collection[$remaining->{$relation['field']}] = $remaining;
                    }
                }

                $this->arrRelated[$key] = new Collection($collection, $strClass::getTable());
            }
        }

        return parent::getRelated($key, $options);
    }

    public function save()
    {
        $values = [];

        foreach ($this->arrRelations as $field => $relation) {
            if ('haste-ManyToMany' === $relation['type']) {
                $values[$field] = $this->$field;
            }
        }

        parent::save();

        foreach ($values as $field => $value) {
            // Check if $value is an array, otherwise don't change the relation table.
            if (\is_array($value)) {
                static::setRelatedValues(static::$strTable, $field, $this->id, $value);
            }
        }

        return $this;
    }

    /**
     * Get the reference values and return them as array.
     */
    public static function getReferenceValues(string $table, string $field, mixed $value = null): array
    {
        $relation = static::getRelation($table, $field);
        $values = (array) $value;
        $order = '';

        /** @var Connection $connection */
        $connection = System::getContainer()->get('database_connection');

        // Preserve the values order by using the force saved values in the table in the
        // ORDER BY statement
        if (1 === \count($values) && $relation['forceSave'] && \array_key_exists(strtolower($field), $connection->createSchemaManager()->listTableColumns($relation['related_table']))) {
            $recordValues = $connection->fetchOne('SELECT '.$field.' FROM '.$relation['related_table'].' WHERE '.$relation['field'].'=? LIMIT 1', [$values[0]]);
            $recordValues = StringUtil::deserialize($recordValues);

            if (\is_array($recordValues) && \count($recordValues) > 0) {
                $order = ' ORDER BY FIND_IN_SET('.$connection->quoteIdentifier($relation['reference_field']).', '.$connection->getDatabasePlatform()->quoteStringLiteral(implode(',', $recordValues)).')';
            }
        }

        return $connection->fetchFirstColumn('SELECT '.$relation['reference_field'].' FROM '.$relation['table'].(!empty($values) ? (' WHERE '.$relation['related_field']." IN ('".implode("','", $values)."')") : '').$order);
    }

    /**
     * Get the related values and return them as array.
     */
    public static function getRelatedValues(string $table, string $field, mixed $value = null): array
    {
        $relation = static::getRelation($table, $field);
        $values = (array) $value;
        $order = '';

        /** @var Connection $connection */
        $connection = System::getContainer()->get('database_connection');

        // Preserve the values order by using the force saved values in the table in the
        // ORDER BY statement
        if (1 === \count($values) && $relation['forceSave'] && \array_key_exists(strtolower($field), $connection->createSchemaManager()->listTableColumns($relation['reference_table']))) {
            $recordValues = $connection->fetchOne('SELECT '.$field.' FROM '.$relation['reference_table'].' WHERE '.$relation['reference'].'=? LIMIT 1', [$values[0]]);
            $recordValues = StringUtil::deserialize($recordValues);

            if (\is_array($recordValues) && \count($recordValues) > 0) {
                $order = ' ORDER BY FIND_IN_SET('.$connection->quoteIdentifier($relation['related_field']).', '.$connection->getDatabasePlatform()->quoteStringLiteral(implode(',', $recordValues)).')';
            }
        }

        return $connection->fetchFirstColumn('SELECT '.$relation['related_field'].' FROM '.$relation['table'].(!empty($values) ? (' WHERE '.$relation['reference_field']." IN ('".implode("','", $values)."')") : '').$order);
    }

    /**
     * Set the related values.
     */
    public static function setRelatedValues(string $table, string $field, mixed $reference, mixed $value): void
    {
        $relation = static::getRelation($table, $field);

        static::deleteRelatedValues($table, $field, $reference);

        /** @var Connection $connection */
        $connection = System::getContainer()->get('database_connection');

        foreach (array_filter((array) $value) as $value) {
            $connection->insert($relation['table'], [$relation['reference_field'] => $reference, $relation['related_field'] => $value]);
        }
    }

    /**
     * Delete the related values.
     */
    public static function deleteRelatedValues(string $table, string $field, mixed $reference): void
    {
        $relation = static::getRelation($table, $field);

        /** @var Connection $connection */
        $connection = System::getContainer()->get('database_connection');
        $connection->delete($relation['table'], [$relation['reference_field'] => $reference]);
    }

    protected static function getRelation(string $table, string $field): array
    {
        $relation = System::getContainer()->get(DcaRelationsManager::class)->getRelation($table, $field);

        if (null === $relation) {
            throw new \InvalidArgumentException(\sprintf('Field %s.%s is not related!', $table, $field));
        }

        return $relation;
    }
}
