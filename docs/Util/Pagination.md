# Haste Pagination

This utility class is designed to help paginate the records.

## Examples ##

Use the pagination with database:

```php
$intTotal = $this->Database->execute("SELECT COUNT(*) AS total FROM tl_table")->total;

$objPagination = new \Haste\Util\Pagination($intTotal, $this->perPage, 'page_i' . $this->id);

if (!$objPagination->isValid()) {
    $objHandler = new $GLOBALS['TL_PTY']['error_404']();
    $objHandler->generate($GLOBALS['objPage']->id);
}

$objItems = $this->Database->prepare("SELECT * FROM tl_table")
    ->limit($objPagination->getLimit(), $objPagination->getOffset())
    ->execute();

$objPagination->addToTemplate($this->Template);
```

Use the pagination with array:

```php
$arrItems = range(1, 10)

$objPagination = new \Haste\Util\Pagination($arrItems, $this->perPage, 'page_i' . $this->id);

if (!$objPagination->isValid()) {
    $objHandler = new $GLOBALS['TL_PTY']['error_404']();
    $objHandler->generate($GLOBALS['objPage']->id);
}

// Paginate the result
if ($objPagination->hasLimit()) {
    $arrItems = array_slice($arrItems, $objPagination->getOffset(), $objPagination->getLimit());
}

$objPagination->addToTemplate($this->Template);
```
