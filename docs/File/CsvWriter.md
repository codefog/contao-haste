# Haste CsvWriter

Provides helper classes to write CSV files. Default configuration:

- delimiter is ; (semicolon)
- enclosure is " (quote)


## Examples ##

### Export data from model ###

```php
<?php

// Initialize data provider object
$objDataProvider = new \Haste\File\CsvWriter\DataProvider\ModelCollection($objModel);

// Set header fields
$objDataProvider->setHeaderFields($arrHeaderFields);

// Initialize the writer object
$objCsvWriter = new Haste\File\CsvWriter($objDataProvider);

// Enable header fields
$objCsvWriter->enableHeaderFields();

// Save the file (returns a \File object)
$objCsvWriter->save('files/csv/my_file.csv');

// Download the file
$objCsvWriter->download();
```

### Map the data ###

```php
<?php

$objDataProvider = new \Haste\File\CsvWriter\DataProvider\DatabaseResult($objResult);
$objCsvWriter = new Haste\File\CsvWriter($objDataProvider);

$objCsvWriter->download('', function($arrRow) {
	return array($arrRow['id'], $arrRow['title']);
});
```