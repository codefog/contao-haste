# DcaRelations component

This component provides methods to handle "many-to-many" relations between tables.

Important notes:

- Please update the database after defining a new relation.
- The relation table name consists of the original table name and related table name unless specified differently, e.g., tl_table_one_table_two.
- If you delete a record in the related table, then the relation tables are automatically updated.
- Automatically adds a filter in the back end if you set `'filter' => true,` like for any other field (note that `filter` has to be in your `panelLayout`)
- Automatically adds a search box in the back end if you set `'search' => true,` like for any other field  (note that `search` has to be in your `panelLayout`). It lists all the fields that are searchable in the related table.
- Relations are always bidirectional. To have unidirectional ones, you need to have separate relation tables.


## Usage

Define the relation in the DCA table of your choice:

```php
<?php

$GLOBALS['TL_DCA']['tl_source_table']['fields']['my_field']['relation'] = [
    'type' => 'haste-ManyToMany', 
    'table' => 'tl_related_table',
];
```

Then, update the database schema. You can now benefit from the relations, e.g., using static methods from our custom model class:

```php
use Codefog\HasteBundle\Model\DcaRelationsModel;

// Get the related values from the related table for source record ID 123
$relatedIds = DcaRelationsModel::getRelatedValues('tl_source_table', 'my_field', 123);

// Get the reference values from the source table for related record IDs 1, 2, 3
$referenceIds = DcaRelationsModel::getReferenceValues('tl_source_table', 'my_field', [1, 2, 3]);
```

Alternatively, you can extend your model class:

```php
use Codefog\HasteBundle\Model\DcaRelationsModel;

class SourceTableModel extends DcaRelationsModel
{
    // …
}
```

Then you can call the methods using the Contao default method:

```php
$related = $sourceTableModel->getRelated('my_field');
```


## Reference

Here is a full list of available configuration options:

```php
$GLOBALS['TL_DCA']['tl_table_one']['fields']['my_field']['relation'] = [
    'type' => 'haste-ManyToMany',
    'table' => 'tl_table_two', // the related table,
    'reference' => 'id', // current table field (optional)
    'referenceSql' => ['type' => \Doctrine\DBAL\Types::INTEGER, 'unsigned' => true, 'default' => 0], // current table field sql definition (optional)
    'referenceColumn' => 'my_reference_field', // a custom column name in relation table (optional)
    'field' => 'id', // related table field (optional)
    'fieldSql' => ['type' => \Doctrine\DBAL\Types::INTEGER, 'unsigned' => true, 'default' => 0], // related table field sql definition (optional)
    'fieldColumn' => 'my_related_field', // a custom column name in relation table (optional)
    'relationTable' => '', // custom relation table name (optional)
    'forceSave' => true, // false by default. If set to true it does not only store the values in the relation tables but also the "my_relation" field
    'skipInstall' => true, // false by default. Do not add relation table. Useful if you use Doctrine relations on the same tables.
];
```

You can also define the relation using Doctrine ORM entities, [read more about it here](DoctrineOrm.md).
