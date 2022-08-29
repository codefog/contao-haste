# API changes – Version 4.* to 5.0

## Conversion to bundle

Haste becomes a regular Contao bundle as of version 5.0. 


## AjaxReload component

The AjaxReload component has become a service and no longer accepts custom IDs:

```php
// Content element (old)
\Haste\Ajax\ReloadHelper::subscribe(
    \Haste\Ajax\ReloadHelper::getUniqid(\Haste\Ajax\ReloadHelper::TYPE_CONTENT, $contentElementId),
    ['foo-event']
);

// Content element (new)
$this->ajaxReloadManager->subscribe(\Codefog\HasteBundle\AjaxReloadManager::TYPE_CONTENT, $contentElementId, ['foo-event']);



// Frontend module (old)
\Haste\Ajax\ReloadHelper::subscribe(
    \Haste\Ajax\ReloadHelper::getUniqid(\Haste\Ajax\ReloadHelper::TYPE_MODULE, $this->id),
    ['bar-event']
);

// Frontend module (new)
$this->ajaxReloadManager->subscribe(AjaxReloadManager::TYPE_MODULE, $frontendModuleId, ['bar-event']);



// Custom (old)
\Haste\Ajax\ReloadHelper::subscribe('my-unique-id', ['baz-event']);

// Custom (new)
// No longer supported!
```


## DcaAjaxOperations component

This component did not change much, but we advise you to switch to the Contao core default features described 
in the [manual](docs/DcaAjaxOperations.md).


## DcaDateRangeFilter component

The class with static methods has been converted to a service.


## Removed components

1. `Haste\Dca\PaletteManipulator` – it is a part of Contao core now.
2. `Haste\Dca\SortingFlag` – it is a part of Contao core now.
3. `Haste\Dca\SortingMode` – it is a part of Contao core now.
4. `Haste\Data` – obsolete stuff.
5. `Haste\DateTime` – use an alternative, for example [nesbot/carbon](https://github.com/briannesbitt/Carbon).
6. `Haste\Frontend` – obsolete stuff.
7. `Haste\Geodesy` – obsolete stuff, use an alternative.
8. `Haste\Generator` – obsolete stuff.
9. `Haste\Haste` – obsolete stuff.
10. `Haste\Http` – use Symfony components instead.
11. `Haste\Input` – auto_item is always active in Contao core now.
12. `Haste\Number` – obsolete stuff.
13. `Haste\Units` – use an alternative, for example [jordanbrauer/unit-converter](https://github.com/jordanbrauer/unit-converter).
14. `Haste\Util\Debug` – obsolete stuff.
15. `Haste\Util\RepositoryVersion` – obsolete stuff.
