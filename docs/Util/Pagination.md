# Haste Pagination

This utility class is designed to help paginate the records.

## Examples ##

Use the pagination with database:

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

Use the pagination with array:

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
