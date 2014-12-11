# Haste Pagination

This utility class is designed to help paginate the records. 


## About offset and limit

The `Haste\Util\Pagination` helper uses the standard Contao Pagination class, but calculates
the limit and offset values for you. The class has specially been designed to always
return a valid value.

If you pass a `perPage` value of 0 (standard in Contao to get all results), the class
will return an offset of `0` and a limit equal to total number of records.


## Checking the pagination range

If your result set has 5 pages, but the URL parameter says to show page 6,
the pagination is out of range. As a developer, you must check for this case using 
the `isOutOfRange` property. The default would be to show a 404 (page not found) error.


## Examples


### Use the pagination with database

```php
$intTotal = $this->Database->execute("SELECT COUNT(*) AS total FROM tl_table")->total;

$objPagination = new \Haste\Util\Pagination($intTotal, $this->perPage, 'page_i' . $this->id);

if ($objPagination->isOutOfRange()) {
    $objHandler = new $GLOBALS['TL_PTY']['error_404']();
    $objHandler->generate($GLOBALS['objPage']->id);
    exit;
}

$objItems = $this->Database->prepare("SELECT * FROM tl_table")
                           ->limit($objPagination->getLimit(), $objPagination->getOffset())
                           ->execute();

$this->Template->pagination = $objPagination->generate();
```


### Use the pagination with array

```php
$arrItems = range(1, 10)

$objPagination = new \Haste\Util\Pagination(count($arrItems), $this->perPage, 'page_i' . $this->id);

if ($objPagination->isOutOfRange()) {
    $objHandler = new $GLOBALS['TL_PTY']['error_404']();
    $objHandler->generate($GLOBALS['objPage']->id);
    exit;
}

// Paginate the result
$arrItems = array_slice($arrItems, $objPagination->getOffset(), $objPagination->getLimit());

$this->Template->pagination = $objPagination->generate();
```
