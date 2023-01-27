# DoctrineOrm component

This component provides methods to integrate Contao and Haste with Doctrine more easily.

## Usage

### Enable relations

Let's assume the example, where one `company` can have multiple `industries`.

To define the `Haste-ManyToMany` relation using Doctrine ORM entities, edit your DCA file:

```php
$GLOBALS['TL_DCA']['tl_app_company']['fields']['industries'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'flag' => 1,
    'foreignKey' => 'tl_app_industry.name',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    
    // Define the relation
    'relation' => [
        'type' => 'haste-ManyToMany', 
        'entity' => \App\Entity\Company::class,
        'property' => 'foobar' // (optional) if the entity property is different than the field name
    ],
];
```

Then, define the relation in Doctrine itself:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

class Company
{
    #[ORM\ManyToMany(\App\Entity\Industry::class)]
    #[ORM\JoinTable('tl_app_rel_company_industry')]
    #[ORM\JoinColumn('company_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn('industry_id', onDelete: 'CASCADE')]
    private $industries;
}
``` 


### Using relations in queries

If you defined the relation like in the above example, you will bea ble to use the relation in the Doctrine 
query builder, for example:

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
use Doctrine\ORM\Mapping as ORM;

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
