# Pagination component

This component is designed to help paginate the records. 


## About offset and limit

The `Codefog\HasteBundle\Util\Pagination` helper uses the standard Contao Pagination class, but calculates
the limit and offset values for you. The class has specially been designed to always
return a valid value.

If you pass a `perPage` value of 0 (standard in Contao to get all results), the class
will return an offset of `0` and a limit equal to total number of records.


## Checking the pagination range

If your result set has 5 pages, but the URL parameter says to show page 6,
the pagination is out of range. As a developer, you must check for this case using
the `isOutOfRange` property. The default would be to show a 404 (page not found) error.


## Usage

### Use the pagination with models

```php
use Codefog\HasteBundle\Util\Pagination;
use Contao\CoreBundle\Exception\PageNotFoundException;

$total = MyModel::countAll();

$pagination = new Pagination($total, $this->perPage, 'page_i' . $this->id);

if ($pagination->isOutOfRange()) {
    throw new PageNotFoundException();
}

$models = MyModel::findAll(['limit' => $pagination->getLimit(), 'offset' => $pagination->getOffset()]);

$template->pagination = $pagination->generate();
```

### Use the pagination with array

```php
use Codefog\HasteBundle\Util\Pagination;
use Contao\CoreBundle\Exception\PageNotFoundException;

$items = range(1, 100)
$total = count($items);

$pagination = new Pagination($total, $this->perPage, 'page_i' . $this->id);

if ($pagination->isOutOfRange()) {
    throw new PageNotFoundException();
}

$items = array_slice($items, $pagination->getOffset(), $pagination->getLimit());

$template->pagination = $pagination->generate();
```
