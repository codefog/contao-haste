# Haste CsvFileWriter

Provides a class to write CSV files. Default configuration:

## Examples ##

### Export data from model ###

```php
<?php

// Initialize reader from model collection
$objReader = new \Haste\IO\Reader\ModelCollectionReader($objModel);

// Initialize writer
$objWriter = new \Haste\IO\Writer\CsvFileWriter());

// Set row callback
$objWriter->setRowCallback(function($arrRow) {
    return array(
        $arrRow['id'],
        \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $arrRow['tstamp']),
        $arrRow['name']
    );
});

// Create a file
$objWriter->writeFrom($objReader);

// Download the file
$objFile = new \File($objWriter->getFilename());
$objFile->sendToBrowser();
```