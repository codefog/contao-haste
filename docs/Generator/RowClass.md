# Haste RowClass

About
-----

Haste RowClass has been designed to ease adding the common CSS classes like first/last or even/odd.


Examples
------------

### Add the first/last and even/odd classes
```php
<?php

while ($objItems->next()) {
    $arrItems[$objItems->id] = $objItems->row();
    // ...
}

\Haste\Generator\RowClass::withKey('class')->addFirstLast()->addEvenOdd()->applyTo($arrItems);
```