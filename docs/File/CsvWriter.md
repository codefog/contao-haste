# Haste CsvWriter

Provides helper classes to write CSV files. Default configuration:

- delimiter is ; (semicolon)
- enclosure is " (quote)


## Examples ##

### A simple save from table ###

```php
<?php

$objExport = new Haste\File\CsvWriter();
$objExport->setTable('tl_news');
$objExport->setMapperFromDca('tl_news');
$objExport->save('files/csv/my_file.csv'); // Returns a file object
```

If you set the mapper from DCA, the fields must have "haste\_csv\_position" property set with column index value, e.g.

```php
$GLOBALS['TL_DCA']['tl_news']['fields']['alias']['eval']['haste_csv_position'] = 2;
```

### Download a file ###

```php
<?php

$objExport = new Haste\File\CsvWriter();
$objExport->setTable('tl_news');
$objExport->setMapperFromDca('tl_news');
$objExport->download();
```

### Advanced usage ###

```php
<?php

$objExport = new Haste\File\CsvWriter();

// Use custom data
$objExport->setModel($objModel);

// Add header fields
$objExport->setHeaderFields(array('ID', 'Headline', 'Alias'));

// Set custom mapper
$objExport->setMapper(array
(
    'id' => 0,
    'headline' => 1,
    'alias' => 2
));

// Add custom parsing
$objExport->save('files/csv/my_file.csv', function($arrData) {
    return array_filter($arrData);
});
```