# DoctrineOrm component

This component provides methods to integrate Contao and Haste with Doctrine more easily.

## Usage

### Enable relations

#### Using relations in queries

Let's assume the following `Haste-ManyToMany` relation:

```php
$GLOBALS['TL_DCA']['tl_app_company']['fields']['industries'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'flag' => 1,
    'foreignKey' => 'tl_app_industry.name',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'relation' => [
        'type' => 'haste-ManyToMany',
        'table' => 'tl_app_industry',
        'relationTable' => 'tl_app_rel_company_industry',
        'fieldColumn' => 'industry_id',
        'referenceColumn' => 'company_id',
        'skipInstall' => true,
    ],
];
```

To ease working with the Doctrine ORM, you should specify the `industries` property like below: 

```php
<?php

namespace App\Entity;

class Company
{
    #[ORM\ManyToMany(Industry::class)]
    #[ORM\JoinTable('tl_app_rel_company_industry')]
    #[ORM\JoinColumn('company_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn('industry_id', onDelete: 'CASCADE')]
    private $industries;
}
```

This will allow you to use the relation in the Doctrine query builder, for example:

```php
$qb = $this->createQueryBuilder('c');
$qb
    ->select('c')
    ->groupBy('c.id')
    ->join('c.industries', 'industries')
    ->andWhere($qb->expr()->in('industries.id', ':industries'))
    ->setParameter(':industries', [1, 2, 3], Connection::PARAM_INT_ARRAY)
;
```


### Enable versioning

To enable the Contao record versioning for an entity, add the `DoctrineOrmVersion` attribute:

```php
<?php

namespace App\Entity;

use Codefog\HasteBundle\Attribute\DoctrineOrmVersion;

#[DoctrineOrmVersion(editRouteParams: ['do' => 'app_companies'])]
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
