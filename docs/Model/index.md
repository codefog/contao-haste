# Haste Model

Provides a methods to handle "many to many" relation between tables.

Important notes:

- Please update the database after defining the new relation.
- The relation table name consists of the original table name and related table name unless specified differently, e.g. tl_table_one_table_two.
- If you delete a record in the related table then the relation tables are automatically updated.


## Examples ##

### Define relation in DCA ###

```php
<?php

$GLOBALS['TL_DCA']['tl_table_one']['fields']['my_field']['relation'] = array
(
    'type' => 'haste-ManyToMany',
    'load' => 'lazy',
    'table' => 'tl_table_two', // the related table
    'reference' => 'id', // current table field (optional)
    'referenceSql' => "int(10) unsigned NOT NULL default '0'", // current table field sql definition (optional)
    'referenceColumn' => 'my_reference_field', // a custom column name in relation table (optional)
    'field' => 'id', // related table field (optional)
    'fieldSql' => "int(10) unsigned NOT NULL default '0'", // related table field sql definition (optional)
    'fieldColumn' => 'my_related_field', // a custom column name in relation table (optional)
    'relationTable' => '', // custom relation table name (optional)
    'forceSave' => true // false by default. If set to true it does not only store the values in the relation tables but also the "my_relation" field
    'bidirectional' => true // false by default. If set to true relations are handled bidirectional (e.g. project A is related to project B but project B is also related to project A)
);
```

### Define model class ###

The model class must extend \Haste\Model\Model.

```php
<?php

class TableOneModel extends \Haste\Model\Model
{
    // ...
}
```

Then call it as usual

```php
$objRelated = $objModel->getRelated('my_field');
```

You can also fetch the related or reference values manually:

```php
$arrRelated = static::getRelatedValues('tl_table_one', 'my_field', 123);

$arrReference = static::getReferenceValues('tl_table_one', 'my_field', array(1, 2, 3));
```