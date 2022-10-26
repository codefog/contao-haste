# ArrayPosition component

This component is designed to help insert new array values in the correct place. 


## Usage

Insert the `foo => bar` values before `test2`:

```php
use Codefog\HasteBundle\Util\ArrayPosition;

$existingArray = ['test1' => 'test', 'test2' => 'test', 'test3' => 'test'];
$newValues = ['foo' => 'bar'];

$newArray = ArrayPosition::before('test2')->addToArray($existingArray, $newValues);

// Result
$newArray = [
    'test1' => 'test', 
    'foo' => 'bar', 
    'test2' => 'test', 
    'test3' => 'test',
];
```

Available positions are:

```php
use Codefog\HasteBundle\Util\ArrayPosition;

ArrayPosition::first();
ArrayPosition::last();

ArrayPosition::before($fieldName);
ArrayPosition::after($fieldName);
```
