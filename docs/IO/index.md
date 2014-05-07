# Haste IO

Provides classes/methods to perform Input/Output operations.

*Be aware that the Haste IO is currently depending to row-based records.*


## Basics ##

The Haste IO component is designed for mass data transfer between different storage engines. This includes

- Load file (e.g. CSV) into database/model collection
- Write records from database/model collection into file (e.g. CSV)
- Convert values while performing import/export


## Features ##


### Reader classes ###

- `ArrayReader` – Read values from a PHP array
- `CsvReader` – Read values from a CSV file (with configurable delimiter/enclosure/escape)
- `DatabaseResultReader` – Read values from a database result
- `ModelCollectionReader` – Read values from a Contao model collection


### Writer classes ###

- `CsvFileWriter` – Write values into a CSV file (with configurable delimiter/enclosure)
- `ExcelFileWriter` – Write values into Excel file (requires *php-excel* Contao extension)
- `ModelWriter` – Write values into Contao models (performs `save` on the model)


### Mappers ###

Mappers can convert input rows into output rows. They are similar to the row callback, but
usually classes that are reusable.

- `ArrayMapper` – Convert array keys based on a mapper array
- `DcaFieldMapper` – Convert array keys based on DCA field definition


## Examples ##

### Export data from model ###

> If you do not set a file name for the Writer (constructor argument), it will create a random file
> in Contao's `system/tmp` folder.


```php
// Initialize reader from model collection
$objReader = new \Haste\IO\Reader\ModelCollectionReader($objModel);

// Initialize writer
$objWriter = new \Haste\IO\Writer\CsvFileWriter();

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
