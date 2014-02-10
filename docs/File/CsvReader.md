# Haste CsvReader

Provides helper classes to read CSV files. Default configuration:

- delimiter is ; (semicolon)
- enclosure is " (quote)
- escape is \\\ (backslash)


## Examples ##

### A simple save to table ###

```php
<?php

$objImport = new Haste\File\CsvReader('system/tmp/news.csv');
$objImport->setTable('tl_news');
$objImport->setMapperFromDca('tl_news');
$objImport->save(); // Returns a list of saved records
```

If you set the mapper from DCA, the fields must have "haste\_csv\_position" property set with column index value, e.g.

```php
$GLOBALS['TL_DCA']['tl_news']['fields']['alias']['eval']['haste_csv_position'] = 2;
```

### A save using model and custom mapper ###

```php
<?php

$objImport = new Haste\File\CsvReader('system/tmp/news.csv');
$objImport->setHeaderFields(true); // Skip header fields
$objImport->setModel('Custom\Model\News');
$objImport->setMapper(array(
    'id' => 0,
    'headline' => 1,
    'alias' => 2
));
$objImport->save(); // Returns a list of saved records
```

### Override default configuration ###

```php
<?php

$objImport->setDelimiter(',');
$objImport->setEnclosure("'");
$objImport->setEscape('\\');
```