# DoctrineOrm component

This component provides methods to integrate Contao and Haste with Doctrine more easily.

## Usage

### Enable versioning

To enable the Contao record versioning for an entity, add the `DoctrineOrmVersion` attribute:

```php
<?php

namespace App\Entity;

use Codefog\HasteBundle\Attribute\DoctrineOrmVersion;

#[DoctrineOrmVersion]
class Company
{
}
```

### Enable "undo" feature

To enable the Contao "undo" feature for an entity, add the `DoctrineOrmUndo` attribute:

```php
<?php

namespace App\Entity;

use Codefog\HasteBundle\Attribute\DoctrineOrmUndo;

#[DoctrineOrmUndo]
class Company
{
}
```
