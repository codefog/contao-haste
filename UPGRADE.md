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


## DcaRelations component

The DCA configurations did not change, but the class changes are BC breaks:

1. The `\Haste\Model\Relations` class has been converted to the `\Codefog\HasteBundle\DcaRelations` service.
2. The model class has been renamed from `\Haste\Model\Model` to `\Haste\Model\DcaRelationsModel`. 


## Form component

The `\Haste\Form` class has been reworked and contains some BC breaks:

- The `tableless` feature has been dropped.
- The form can now be initialized without the "submit check" callback.
- The `__toString()` method has been dropped.
- The `splitHiddenAndVisibleWidgets()` method has been dropped.
- The `addSubmitFormField()` method has a different argument order. Previously it was `addSubmitFormField($fieldName, $label, $position = null)` and now it is `addSubmitFormField($label, $fieldName = 'submit', $position = null')`.
- The `isDirty()` method has been dropped. Please use `getCurrentState()` instead.
- The `addToTemplate()` method has been dropped. Please use `addToObject()` instead.
- The `getMethod()` method has been renamed to `getHttpMethod()`.
- The `isNoValidate()` method has been renamed to `isDisableHtmlValidation()`.
- The `setNoValidate()` method has been renamed to `setDisableHtmlValidation()`.
- The `setIsSubmitted()` method has been renamed to `setIsSubmitted()`.
- The `bindModel()` method has been renamed to `setBoundModel()`.
- The `getFormAction()` method has been renamed to `getAction()`.
- The `setFormActionFromUri()` method has been renamed to `setAction()`.
- The `setFormActionFromPageId()` method has been renamed to `setActionFromPageId()`.
- The `generateNoValidate()` method has been renamed to `generateNoValidateAttribute()`.

Please refer to the [manual](docs/Form.md) for new code examples.


## Removed components

- `Haste\Dca\PaletteManipulator` – it is a part of Contao core now.
- `Haste\Dca\SortingFlag` – it is a part of Contao core now.
- `Haste\Dca\SortingMode` – it is a part of Contao core now.
- `Haste\Data` – obsolete stuff.
- `Haste\DateTime` – use an alternative, for example [nesbot/carbon](https://github.com/briannesbitt/Carbon).
- `Haste\Frontend` – obsolete stuff.
- `Haste\Geodesy` – obsolete stuff, use an alternative.
- `Haste\Generator` – obsolete stuff.
- `Haste\Haste` – obsolete stuff.
- `Haste\Http` – use Symfony components instead.
- `Haste\Image` – obsolete stuff.
- `Haste\Input` – auto_item is always active in Contao core now.
- `Haste\IO` – use an alternative, for example [thephpleague/csv](https://github.com/thephpleague/csv) or [PHPOffice/PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet).
- `Haste\Number` – obsolete stuff.
- `Haste\Units` – use an alternative, for example [jordanbrauer/unit-converter](https://github.com/jordanbrauer/unit-converter).
- `Haste\Util\Debug` – obsolete stuff.
- `Haste\Util\RepositoryVersion` – obsolete stuff.
