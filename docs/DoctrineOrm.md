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

#### Proper relations handling

To remove the child records while adding them to the `tl_undo` table, you have to add the `cascade: ['remove']` attribute
property on the parent entity. You also have to set the `DoctrineOrmUndo` attribute on the child entity.

> Known limitation is that the deleted related records will be stored as separate `tl_undo` entries, as it's not yet
> clear how to determine the entity (e.g. `Parent`) that triggered the remove process. The Doctrine ORM starts
> removing records from child records. 

> Make sure you do not set the `onDelete: 'CASCADE'` on the child entity, as it will cause the records to be removed
> on the database level and they won't be added to the `tl_undo` table.

```php
<?php

namespace App\Entity;

use Codefog\HasteBundle\Attribute\DoctrineOrmUndo;

#[DoctrineOrmUndo]
class Parent
{
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Child::class, cascade: ['remove'])]
    private Collection $childs;
}

#[DoctrineOrmUndo]
class Child 
{
    #[ORM\ManyToOne(targetEntity: Parent::class, inversedBy: 'childs')]
    #[ORM\JoinColumn(name: 'pid', referencedColumnName: 'id', options: ['default' => 0])]
    private Parent|null $parent = null;
}
```
