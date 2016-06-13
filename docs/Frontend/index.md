# Haste Frontend

Various classes to improve Contao frontend handling.


## AbstractFrontendModule

This class extends `Contao\Module` and helps reducing duplicate code by
providing a `generateWildcard` method for the Contao backend.

By default it is also applied in `generate()`, but if you need to change
the `generate()` method just call the parent method.


### Example 1
 
No need to have a `generate()` method if you simply want the wildcard in BE.
 
```php
class MyModule extends \Haste\Frontend\AbstractFrontendModule
{
    
    protected function compile() 
    {
        // just compile your module, TL_MODE === 'BE' will return wildcard
    }
}
```

### Example 2
Override `generate()` and call `generateWildcard()` if you need special handling.

```php
class MyModule extends \Haste\Frontend\AbstractFrontendModule
{

    /**
     * Check if template is not empty 
     * 
     * @return string
     */
    public function generate()
    {
        if ('BE' === TL_MODE) {
            return $this->generateWildcard();
        }
        
        $buffer = parent::generate();
        
        if (0 === count($this->Template->items)) {
            return '';
        }
        
        return $buffer;
    }
    
    
    protected function compile()
    {
        // do something        
    }
}
```
