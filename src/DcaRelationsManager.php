<?php

declare(strict_types=1);

namespace Codefog\HasteBundle;

use Codefog\HasteBundle\Model\DcaRelationsModel;
use Contao\ArrayUtil;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\String\UnicodeString;
use Terminal42\DcMultilingualBundle\Driver;

class DcaRelationsManager
{
    private array $relationsCache = [];

    private array $filterableFields = [];

    private array $searchableFields = [];

    /**
     * This cache stores the table and record ID that has been already purged. It
     * allows you to have multiple fields with the same relation in one DCA and
     * prevents the earlier field values to be removed by the last one (the helper
     * table is purged only once in this case, for the first field).
     */
    private array $purgeCache = [];

    /**
     * This cache is in fact a hotfix for the "override all" mode. It simply does not
     * allow the last record to be double-saved.
     */
    private array $overrideAllCache = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly Formatter $formatter,
        private readonly RequestStack $requestStack,
        private readonly ResourceFinderInterface $resourceFinder,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly UndoManager $undoManager,
        private readonly EntityManager|null $entityManager = null,
    ) {
    }

    #[AsHook('loadDataContainer')]
    public function addRelationCallbacks(string $table): void
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['fields'])) {
            return;
        }

        $blnCallbacks = false;

        foreach ($GLOBALS['TL_DCA'][$table]['fields'] as $fieldName => $fieldConfig) {
            if (($relation = $this->getRelation($table, $fieldName)) === null) {
                continue;
            }

            $blnCallbacks = true;

            // Update the field configuration
            $GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['eval']['doNotSaveEmpty'] = true;
            $GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['load_callback'][] = [static::class, 'getRelatedRecords'];
            $GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['save_callback'][] = [static::class, 'updateRelatedRecords'];

            // Use custom filtering
            if (isset($fieldConfig['filter']) && $fieldConfig['filter']) {
                $GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['filter'] = false;
                $this->filterableFields[$table][$fieldName] = $relation;
            }

            // Use custom search filtering
            if (isset($fieldConfig['search']) && $fieldConfig['search']) {
                $GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['search'] = false;
                $this->searchableFields[$table][$fieldName] = $relation;
            }
        }

        // Add global callbacks
        if ($blnCallbacks) {
            $GLOBALS['TL_DCA'][$table]['config']['ondelete_callback'][] = [static::class, 'deleteRelatedRecords'];
            $GLOBALS['TL_DCA'][$table]['config']['oncopy_callback'][] = [static::class, 'copyRelatedRecords'];
        }

        $GLOBALS['TL_DCA'][$table]['config']['ondelete_callback'][] = [static::class, 'cleanRelatedRecords'];

        // Add filter and search callbacks for the backend only
        if (($request = $this->requestStack->getCurrentRequest()) !== null && $this->scopeMatcher->isBackendRequest($request)) {
            if (\count($this->filterableFields[$table] ?? []) > 0) {
                $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = [static::class, 'filterByRelations'];

                if (isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'])) {
                    $GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'] = preg_replace('/filter/', 'haste_filter;filter', (string) $GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'], 1);
                    $GLOBALS['TL_DCA'][$table]['list']['sorting']['panel_callback']['haste_filter'] = [static::class, 'addRelationFilters'];
                }
            }

            if (\count($this->searchableFields[$table] ?? []) > 0) {
                $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = [static::class, 'filterBySearch'];

                if (isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'])) {
                    $GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'] = preg_replace('/search/', 'haste_search;search', (string) $GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'], 1);
                    $GLOBALS['TL_DCA'][$table]['list']['sorting']['panel_callback']['haste_search'] = [static::class, 'addRelationSearch'];
                }
            }
        }
    }

    /**
     * Update the records in related table.
     */
    public function updateRelatedRecords(mixed $value, DataContainer $dc): mixed
    {
        if (($relation = $this->getRelation($dc->table, $dc->field)) === null) {
            return $value;
        }

        $cacheKey = $relation['table'].$dc->activeRecord->{$relation['reference']};
        $field = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field] ?? [];

        // Support for csv values
        if (($field['eval']['multiple'] ?? false) && ($field['eval']['csv'] ?? false)) {
            $values = !$value ? [] : explode($field['eval']['csv'], (string) $value);
        } else {
            $values = StringUtil::deserialize($value, true);
        }

        // Check the purge cache
        if (!\in_array($cacheKey, $this->purgeCache, true)) {
            $this->purgeRelatedRecords($relation, $dc->activeRecord->{$relation['reference']});
            $this->purgeCache[] = $cacheKey;
        }

        $saveRecords = true;

        // Do not save the record again in "override all" mode if it has been saved already
        if ('overrideAll' === Input::get('act')) {
            if (\in_array($cacheKey, $this->overrideAllCache, true)) {
                $saveRecords = false;
            }

            $this->overrideAllCache[] = $cacheKey;
        }

        // Save the records in a relation table
        if ($saveRecords) {
            foreach ($values as $v) {
                $this->connection->insert($relation['table'], [
                    $relation['reference_field'] => $dc->activeRecord->{$relation['reference']},
                    $relation['related_field'] => $v,
                ]);
            }
        }

        // Force save the value
        if ($relation['forceSave']) {
            return $value;
        }

        return null;
    }

    /**
     * Delete the records in related table.
     */
    public function deleteRelatedRecords(DataContainer $dc, int|string $undoId): void
    {
        if (!$undoId || ($dc instanceof Driver && '' !== $dc->getCurrentLanguage())) {
            return;
        }

        $this->deleteRelatedRecordsWithUndo($dc->table, $dc->id, (int) $undoId);
    }

    /**
     * Delete the related records with keeping the "undo" data.
     */
    public function deleteRelatedRecordsWithUndo(string $sourceTable, int|string $sourceId, int $undoId): void
    {
        $this->framework->initialize();
        $this->loadDataContainers();

        $undo = [];

        foreach ($GLOBALS['TL_DCA'] as $table => $dca) {
            foreach (array_keys($dca['fields'] ?? []) as $fieldName) {
                $relation = $this->getRelation($table, $fieldName);

                if (null === $relation || ($relation['reference_table'] !== $sourceTable && $relation['related_table'] !== $sourceTable)) {
                    continue;
                }

                // Store the related values for further save in tl_undo table
                if ($relation['reference_table'] === $sourceTable) {
                    $undo[] = [
                        'table' => $sourceTable,
                        'relationTable' => $table,
                        'relationField' => $fieldName,
                        'reference' => $sourceId,
                        'values' => DcaRelationsModel::getRelatedValues($table, $fieldName, $sourceId),
                    ];

                    $this->purgeRelatedRecords($relation, $sourceId);
                } else {
                    $undo[] = [
                        'table' => $sourceTable,
                        'relationTable' => $table,
                        'relationField' => $fieldName,
                        'reference' => $sourceId,
                        'values' => DcaRelationsModel::getReferenceValues($table, $fieldName, $sourceId),
                    ];

                    $this->purgeRelatedRecords($relation, $sourceId);
                }
            }
        }

        // Store the relations in the tl_undo table
        if (\count($undo) > 0) {
            $this->undoManager->add($undoId, 'haste_relations', $undo);
        }
    }

    /**
     * Undo the relations.
     */
    public function undoRelations(array $data, int $id, string $table, array $row): void
    {
        if (!\is_array($data['haste_relations']) || 0 === \count($data['haste_relations'])) {
            return;
        }

        foreach ($data['haste_relations'] as $relationData) {
            if ($relationData['table'] !== $table) {
                continue;
            }

            $relation = $this->getRelation($relationData['relationTable'], $relationData['relationField']);
            $isReferenceTable = $relation['reference_table'] === $table;
            $fieldName = $isReferenceTable ? $relation['reference'] : $relation['field'];

            // Continue if there is no relation or reference value does not match
            if (null === $relation || empty($relationData['values']) || (string) $relationData['reference'] !== (string) $row[$fieldName]) {
                continue;
            }

            foreach ($relationData['values'] as $value) {
                $this->connection->insert($relation['table'], [
                    $relation['reference_field'] => $isReferenceTable ? $id : $value,
                    $relation['related_field'] => $isReferenceTable ? $value : $id,
                ]);
            }
        }
    }

    /**
     * Copy the records in related table.
     */
    public function copyRelatedRecords(int $id, DataContainer $dc): void
    {
        if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'])) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'] as $fieldName => $fieldConfig) {
            if (($fieldConfig['eval']['doNotCopy'] ?? false) || ($relation = $this->getRelation($dc->table, $fieldName)) === null) {
                continue;
            }

            $reference = $id;

            // Get the reference value (if not an ID)
            if ('id' !== $relation['reference']) {
                $referenceRecord = $this->connection->fetchOne('SELECT '.$relation['reference'].' FROM '.$dc->table.' WHERE id=?', [$id]);

                if (false !== $referenceRecord) {
                    $reference = $referenceRecord;
                }
            }

            $values = $this->connection->fetchFirstColumn('SELECT '.$relation['related_field'].' FROM '.$relation['table'].' WHERE '.$relation['reference_field'].'=?', [$dc->{$relation['reference']}]);

            foreach ($values as $value) {
                $this->connection->insert($relation['table'], [
                    $relation['reference_field'] => $reference,
                    $relation['related_field'] => $value,
                ]);
            }
        }
    }

    /**
     * Clean the records in related table.
     */
    public function cleanRelatedRecords(): void
    {
        $dc = null;

        // Try to find the \DataContainer instance (see #37)
        foreach (\func_get_args() as $arg) {
            if ($arg instanceof DataContainer) {
                $dc = $arg;
                break;
            }
        }

        if (null === $dc) {
            throw new \RuntimeException('There seems to be no valid DataContainer instance!');
        }

        if ($dc instanceof Driver && '' !== $dc->getCurrentLanguage()) {
            return;
        }

        $this->loadDataContainers();

        foreach ($GLOBALS['TL_DCA'] as $table => $dca) {
            if (!isset($dca['fields'])) {
                continue;
            }

            foreach (array_keys($dca['fields']) as $fieldName) {
                $relation = $this->getRelation($table, $fieldName);

                if (null === $relation || $relation['related_table'] !== $dc->table) {
                    continue;
                }

                $this->connection->delete($relation['table'], [$relation['related_field'] => $dc->{$relation['field']}]);
            }
        }
    }

    #[AsHook('reviseTable')]
    public function reviseRelatedRecords(string $table, array|null $ids = null): bool
    {
        if (null === $ids || 0 === \count($ids) || !isset($GLOBALS['TL_DCA'][$table]['fields'])) {
            return false;
        }

        foreach (array_keys($GLOBALS['TL_DCA'][$table]['fields']) as $fieldName) {
            if (($relation = $this->getRelation($table, $fieldName)) === null) {
                continue;
            }

            $values = $this->connection->fetchFirstColumn(
                \sprintf(
                    'SELECT %s FROM %s WHERE id IN (?) AND tstamp=0',
                    $this->connection->quoteIdentifier($relation['reference']),
                    $this->connection->quoteIdentifier($table),
                ),
                [$ids],
                [ArrayParameterType::INTEGER],
            );

            foreach ($values as $value) {
                $this->purgeRelatedRecords($relation, $value);
            }
        }

        return false;
    }

    /**
     * Get related records of particular field.
     */
    public function getRelatedRecords(mixed $value, DataContainer $dc): mixed
    {
        if (($relation = $this->getRelation($dc->table, $dc->field)) !== null) {
            $value = DcaRelationsModel::getRelatedValues($dc->table, $dc->field, $dc->{$relation['reference']});
        }

        return $value;
    }

    public function appendToSchema(Schema $schema): void
    {
        foreach ($this->connection->createSchemaManager()->listTables() as $table) {
            $tableName = $table->getName();

            if (!str_starts_with($tableName, 'tl_')) {
                continue;
            }

            Controller::loadDataContainer($tableName);

            if (!isset($GLOBALS['TL_DCA'][$tableName]['fields'])) {
                continue;
            }

            foreach (array_keys($GLOBALS['TL_DCA'][$tableName]['fields']) as $fieldName) {
                $relation = $this->getRelation($tableName, $fieldName);

                if (null === $relation || $relation['skipInstall']) {
                    continue;
                }

                $referenceType = $relation['reference_sql']['type'];
                unset($relation['reference_sql']['type']);

                $relatedType = $relation['related_sql']['type'];
                unset($relation['related_sql']['type']);

                $schemaTable = $schema->hasTable($relation['table']) ? $schema->getTable($relation['table']) : $schema->createTable($relation['table']);

                if (!$schemaTable->hasColumn($relation['reference_field'])) {
                    $schemaTable->addColumn($relation['reference_field'], $referenceType, $relation['reference_sql']);
                }
                if (!$schemaTable->hasColumn($relation['related_field'])) {
                    $schemaTable->addColumn($relation['related_field'], $relatedType, $relation['related_sql']);
                }

                $indexName = $relation['reference_field'].'_'.$relation['related_field'];

                // Add the index only if there is no other (avoid duplicate keys)
                if (!$schemaTable->hasIndex($indexName)) {
                    $schemaTable->addUniqueIndex([$relation['reference_field'], $relation['related_field']], $indexName);
                }
            }
        }
    }

    /**
     * Filter records by relations set in custom filter.
     */
    public function filterByRelations(DataContainer $dc): void
    {
        if (0 === \count($this->filterableFields[$dc->table] ?? []) || ($request = $this->requestStack->getCurrentRequest()) === null) {
            return;
        }

        $rootIds = isset($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root']) && \is_array($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root']) ? $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] : [];

        // Include the child records in tree view
        if (($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? null) === DataContainer::MODE_TREE && \count($rootIds) > 0) {
            $rootIds = Database::getInstance()->getChildRecords($rootIds, $dc->table, false, $rootIds);
        }

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $request->getSession()->getBag('contao_backend');

        $doFilter = false;
        $sessionData = $sessionBag->all();
        $filterId = ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? null) === DataContainer::MODE_PARENT ? $dc->table.'_'.$dc->currentPid : $dc->table;

        foreach (array_keys($this->filterableFields[$dc->table]) as $field) {
            if (isset($sessionData['filter'][$filterId][$field])) {
                $doFilter = true;
                $ids = DcaRelationsModel::getReferenceValues($dc->table, $field, $sessionData['filter'][$filterId][$field]);
                $rootIds = 0 === \count($rootIds) ? $ids : array_intersect($rootIds, $ids);
            }
        }

        if ($doFilter) {
            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = 0 === \count($rootIds) ? [0] : array_unique($rootIds);
        }
    }

    /**
     * Filter records by relation search.
     */
    public function filterBySearch(DataContainer $dc): void
    {
        if (0 === \count($this->searchableFields[$dc->table] ?? []) || ($request = $this->requestStack->getCurrentRequest()) === null) {
            return;
        }

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $request->getSession()->getBag('contao_backend');

        $rootIds = \is_array($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] ?? null) ? $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] : [];
        $doFilter = false;
        $sessionData = $sessionBag->all();

        foreach ($this->searchableFields[$dc->table] as $field => $relation) {
            $relatedTable = $relation['related_table'];

            Controller::loadDataContainer($relatedTable);

            if (
                isset($sessionData['haste_search'][$dc->table])
                && '' !== $sessionData['haste_search'][$dc->table]['searchValue']
                && $relatedTable === $sessionData['haste_search'][$dc->table]['table']
                && $field === $sessionData['haste_search'][$dc->table]['field']
            ) {
                $doFilter = true;
                $query = \sprintf(
                    'SELECT %s.%s AS sourceId FROM %s INNER JOIN %s ON %s.%s = %s.%s INNER JOIN %s ON %s.%s = %s.%s',
                    $dc->table,
                    $relation['reference'],
                    $dc->table,
                    $relation['table'],
                    $dc->table,
                    $relation['reference'],
                    $relation['table'],
                    $relation['reference_field'],
                    $relation['related_table'],
                    $relation['related_table'],
                    $relation['field'],
                    $relation['table'],
                    $relation['related_field'],
                );

                $procedure = [];
                $values = [];

                $strPattern = 'CAST(%s AS CHAR) REGEXP ?';

                if (str_ends_with((string) Config::get('dbCollation'), '_ci')) {
                    $strPattern = 'LOWER(CAST(%s AS CHAR)) REGEXP LOWER(?)';
                }

                $fld = $relation['related_table'].'.'.$sessionData['haste_search'][$dc->table]['searchField'];

                if (isset($GLOBALS['TL_DCA'][$relatedTable]['fields'][$fld]['foreignKey'])) {
                    [$t, $f] = explode('.', (string) $GLOBALS['TL_DCA'][$relatedTable]['fields'][$fld]['foreignKey']);
                    $procedure[] = '('.\sprintf($strPattern, $fld).' OR '.\sprintf($strPattern, "(SELECT $f FROM $t WHERE $t.id={$relatedTable}.$fld)").')';
                    $values[] = $sessionData['haste_search'][$dc->table]['searchValue'];
                } else {
                    $procedure[] = \sprintf($strPattern, $fld);
                }

                $values[] = $sessionData['haste_search'][$dc->table]['searchValue'];

                $query .= ' WHERE '.implode(' AND ', $procedure);

                $ids = $this->connection->fetchAllAssociative($query, $values);
                $ids = array_column($ids, 'sourceId');

                $rootIds = 0 === \count($rootIds) ? $ids : array_intersect($rootIds, $ids);
            }
        }

        if ($doFilter) {
            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = 0 === \count($rootIds) ? [0] : array_unique($rootIds);
        }
    }

    /**
     * Add the relation filters.
     */
    public function addRelationFilters(DataContainer $dc): string
    {
        if (0 === \count($this->filterableFields[$dc->table] ?? []) || ($request = $this->requestStack->getCurrentRequest()) === null) {
            return '';
        }

        $filter = ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? null) === DataContainer::MODE_PARENT ? $dc->table.'_'.$dc->currentPid : $dc->table;

        /** @var AttributeBagInterface $session */
        $session = $request->getSession()->getBag('contao_backend');
        $sessionData = $session->all();

        // Set filter from user input
        if ('tl_filters' === Input::post('FORM_SUBMIT')) {
            foreach (array_keys($this->filterableFields[$dc->table]) as $field) {
                if (Input::post($field, true) !== 'tl_'.$field) {
                    $sessionData['filter'][$filter][$field] = Input::post($field, true);
                } else {
                    unset($sessionData['filter'][$filter][$field]);
                }
            }

            $session->replace($sessionData);
        }

        $count = 0;
        $return = '<div class="tl_filter tl_subpanel">
<strong>'.$GLOBALS['TL_LANG']['HST']['advanced_filter'].'</strong> ';

        foreach ($this->filterableFields[$dc->table] as $field => $relation) {
            $return .= '<select name="'.$field.'" class="tl_select tl_chosen'.(isset($session['filter'][$filter][$field]) ? ' active' : '').'">
    <option value="tl_'.$field.'">'.($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label'][0] ?? '').'</option>
    <option value="tl_'.$field.'">---</option>';

            $ids = DcaRelationsModel::getRelatedValues($relation['reference_table'], $field);

            if (0 === \count($ids)) {
                $return .= '</select> ';

                // Add the line-break after 5 elements
                if (0 === ++$count % 5) {
                    $return .= '<br>';
                }

                continue;
            }

            $options = array_unique($ids);
            $options_callback = [];

            // Store the field name to be used e.g. in the options_callback
            $dc->field = $field;

            // Call the options_callback
            if ((\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'] ?? null) || \is_callable($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'] ?? null)) && !($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'] ?? null)) {
                if (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'] ?? null)) {
                    $class = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'][0];
                    $method = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'][1];

                    $options_callback = System::importStatic($class)->$method($dc);
                } elseif (\is_callable($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback'] ?? null)) {
                    $options_callback = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options_callback']($dc);
                }

                // Sort options according to the keys of the callback array
                $options = array_intersect(array_keys($options_callback), $options);
            }

            $options_sorter = [];

            // Options
            foreach ($options as $vv) {
                $value = $vv;

                // Options callback
                if (!empty($options_callback) && \is_array($options_callback)) {
                    $vv = $options_callback[$vv];
                } elseif (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['foreignKey'])) {
                    // Replace the ID with the foreign key
                    $key = explode('.', (string) $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['foreignKey'], 2);

                    $parent = $this->connection->fetchOne('SELECT '.$key[1].' FROM '.$key[0].' WHERE id=?', [$vv]);

                    if (false !== $parent) {
                        $vv = $parent;
                    }
                }

                $option_label = '';

                // Use reference array
                if (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'])) {
                    $option_label = \is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'][$vv]) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'][$vv][0] : $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['reference'][$vv];
                } elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['isAssociative'] ?? false) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options'] ?? null)) {
                    // Associative array
                    $option_label = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['options'][$vv] ?? '';
                }

                // No empty options allowed
                if (!\strlen((string) $option_label)) {
                    $option_label = $vv ?: '-';
                }

                $selected = isset($session['filter'][$filter][$field]) && (string) $value === (string) $session['filter'][$filter][$field];

                if ($selected) {
                    $dc->setPanelState(true);
                }

                $options_sorter['  <option value="'.StringUtil::specialchars($value).'"'.($selected ? ' selected="selected"' : '').'>'.$option_label.'</option>'] = (new UnicodeString((string) $option_label))->ascii()->toString();
            }

            $return .= "\n".implode("\n", array_keys($options_sorter));
            $return .= '</select> ';

            // Add the line-break after 5 elements
            if (0 === ++$count % 5) {
                $return .= '<br>';
            }
        }

        return $return.'</div>';
    }

    /**
     * Adds search fields for relations.
     */
    public function addRelationSearch(DataContainer $dc): string
    {
        if (0 === \count($this->searchableFields[$dc->table] ?? []) || ($request = $this->requestStack->getCurrentRequest()) === null) {
            return '';
        }

        $return = '<div class="tl_filter tl_subpanel">';

        /** @var AttributeBagInterface $session */
        $session = $request->getSession()->getBag('contao_backend');
        $sessionValues = $session->get('haste_search');

        // Search field per relation
        foreach ($this->searchableFields[$dc->table] as $field => $relation) {
            // Get searchable fields from related table
            $relatedSearchFields = [];
            $relTable = $relation['related_table'];

            Controller::loadDataContainer($relTable);

            foreach ((array) $GLOBALS['TL_DCA'][$relTable]['fields'] as $relatedField => $dca) {
                if (isset($dca['search']) && true === $dca['search']) {
                    $relatedSearchFields[] = $relatedField;
                }
            }

            if (0 === \count($relatedSearchFields)) {
                continue;
            }

            // Store search value in the current session
            if ('tl_filters' === Input::post('FORM_SUBMIT')) {
                $fieldName = Input::post('tl_field_'.$field, true);
                $keyword = ltrim((string) Input::postRaw('tl_value_'.$field), '*');

                if ($fieldName && !\in_array($fieldName, $relatedSearchFields, true)) {
                    $fieldName = '';
                    $keyword = '';
                }

                // Make sure the regular expression is valid
                if ($fieldName && $keyword) {
                    try {
                        $this->connection->fetchOne('SELECT id FROM '.$relTable.' WHERE '.$fieldName.' REGEXP ? LIMIT 1', [$keyword]);
                    } catch (\Exception) {
                        $keyword = '';
                    }
                }

                $session->set('haste_search', [$dc->table => [
                    'field' => $field,
                    'table' => $relTable,
                    'searchField' => $fieldName,
                    'searchValue' => $keyword,
                ]]);
            }

            $return .= '<div class="tl_search tl_subpanel">';
            $return .= '<strong>'.\sprintf($GLOBALS['TL_LANG']['HST']['advanced_search'], $this->formatter->dcaLabel($dc->table, $field)).'</strong> ';

            $options_sorter = [];

            foreach ($relatedSearchFields as $relatedSearchField) {
                $option_label = $GLOBALS['TL_DCA'][$relTable]['fields'][$relatedSearchField]['label'][0] ?: (\is_array($GLOBALS['TL_LANG']['MSC'][$relatedSearchField] ?? null) ? $GLOBALS['TL_LANG']['MSC'][$relatedSearchField][0] : ($GLOBALS['TL_LANG']['MSC'][$relatedSearchField] ?? ''));
                $options_sorter[(new UnicodeString($option_label))->ascii()->toString().'_'.$relatedSearchField] = '  <option value="'.StringUtil::specialchars($relatedSearchField).'"'.($relatedSearchField === $sessionValues[$dc->table]['searchField'] && $sessionValues[$dc->table]['table'] === $relTable ? ' selected="selected"' : '').'>'.$option_label.'</option>';
            }

            // Sort by option values
            uksort($options_sorter, strnatcasecmp(...));
            $active = $sessionValues[$dc->table]['searchValue'] && $sessionValues[$dc->table]['table'] === $relTable;

            if ($active) {
                $dc->setPanelState(true);
            }

            $return .= '<select name="tl_field_'.$field.'" class="tl_select tl_chosen'.($active ? ' active' : '').'">
            '.implode("\n", $options_sorter).'
            </select>
            <span>=</span>
            <input type="search" name="tl_value_'.$field.'" class="tl_text'.($active ? ' active' : '').'" value="'.StringUtil::specialchars($sessionValues[$dc->table]['searchValue']).'"></div>';
        }

        return $return.'</div>';
    }

    /**
     * Get the relation of particular field in the table.
     */
    public function getRelation(string $table, string $fieldName): array|null
    {
        Controller::loadDataContainer($table);

        $cacheKey = $table.'_'.$fieldName;

        if (!\array_key_exists($cacheKey, $this->relationsCache)) {
            $relation = null;

            if (($GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['relation']['type'] ?? null) === 'haste-ManyToMany') {
                $fieldConfig = &$GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['relation'];

                // Load from entity
                if (isset($fieldConfig['entity'])) {
                    if (null === $this->entityManager) {
                        throw new \RuntimeException(\sprintf('The entity has been defined in the relation for %s.%s, but there is no entity manager service!', $table, $fieldName));
                    }

                    $metaData = $this->entityManager->getClassMetadata($fieldConfig['entity']);

                    if ($metaData->hasAssociation($fieldName)) {
                        $association = $metaData->getAssociationMapping($fieldConfig['property'] ?? $fieldName);

                        $relation = [
                            // The relations table
                            'table' => $association['joinTable']['name'],

                            // The related field
                            'reference' => $association['joinTable']['joinColumns'][0]['referencedColumnName'],
                            'field' => $association['joinTable']['inverseJoinColumns'][0]['referencedColumnName'],

                            // Current table data
                            'reference_table' => $table,
                            'reference_field' => $association['joinTable']['joinColumns'][0]['name'],

                            // Related table data
                            'related_table' => $this->entityManager->getClassMetadata($association['targetEntity'])->getTableName(),
                            'related_field' => $association['joinTable']['inverseJoinColumns'][0]['name'],

                            // Force save
                            'forceSave' => $fieldConfig['forceSave'] ?? null,

                            // Skip installation
                            'skipInstall' => true,
                        ];

                        // Set the table name directly in the relation of DCA field, so the DcaExtractor
                        // will not complain about incomplete relation
                        if (!isset($fieldConfig['table'])) {
                            $GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['relation']['table'] = $relation['related_table'];
                        }
                    }
                } elseif (isset($fieldConfig['table'])) {
                    $relation = [];

                    // The relations table
                    $relation['table'] = $fieldConfig['relationTable'] ?? $this->getTableName($table, $fieldConfig['table']);

                    // The related field
                    $relation['reference'] = $fieldConfig['reference'] ?? 'id';
                    $relation['field'] = $fieldConfig['field'] ?? 'id';

                    // Current table data
                    $relation['reference_table'] = $table;
                    $relation['reference_field'] = $fieldConfig['referenceColumn'] ?? (str_replace('tl_', '', $table).'_'.$relation['reference']);
                    $relation['reference_sql'] = $fieldConfig['referenceSql'] ?? ['type' => Types::INTEGER, 'unsigned' => true, 'default' => 0];

                    if (!\is_array($relation['reference_sql'])) {
                        throw new \RuntimeException('The relation key "referenceSql" must be an array!');
                    }

                    // Related table data
                    $relation['related_table'] = $fieldConfig['table'];
                    $relation['related_field'] = $fieldConfig['fieldColumn'] ?? (str_replace('tl_', '', (string) $fieldConfig['table']).'_'.$relation['field']);
                    $relation['related_sql'] = $fieldConfig['fieldSql'] ?? ['type' => Types::INTEGER, 'unsigned' => true, 'default' => 0];

                    if (!\is_array($relation['related_sql'])) {
                        throw new \RuntimeException('The relation key "fieldSql" must be an array!');
                    }

                    // Force save
                    $relation['forceSave'] = $fieldConfig['forceSave'] ?? null;

                    // Bidirectional
                    $relation['bidirectional'] = true; // I'm here for BC only

                    // Do not add table in install tool
                    $relation['skipInstall'] = (bool) ($fieldConfig['skipInstall'] ?? false);
                }
            }

            $this->relationsCache[$cacheKey] = $relation;
        }

        return $this->relationsCache[$cacheKey];
    }

    /**
     * Get the relations table name in the following format (sorted alphabetically):
     * Parameters: tl_table_one, tl_table_two Returned value: tl_table_one_table_two.
     */
    public function getTableName(string $tableOne, string $tableTwo): string
    {
        $tables = [$tableOne, $tableTwo];
        natcasesort($tables);
        $tables = array_values($tables);

        return $tables[0].'_'.str_replace('tl_', '', $tables[1]);
    }

    /**
     * Load all data containers.
     */
    protected function loadDataContainers(): void
    {
        $processed = [];

        $files = $this->resourceFinder->findIn('dca')->depth(0)->files()->name('*.php');

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (\in_array($file->getBasename(), $processed, true)) {
                continue;
            }

            $processed[] = $file->getBasename();

            Controller::loadDataContainer($file->getBasename('.php'));
        }
    }

    /**
     * Purge the related records.
     */
    protected function purgeRelatedRecords(array $relation, mixed $reference): void
    {
        $this->connection->delete($relation['table'], [$relation['reference_field'] => $reference]);
    }
}
