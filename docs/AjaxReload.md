# AjaxReload component

This component is designed to help you handle the ajax reload of frontend modules and content elements. 


## Usage

First of all, you need to subscribe your frontend module or content element to the events:

```php
use Codefog\HasteBundle\AjaxReloadManager;

// Content element
$this->ajaxReloadManager->subscribe(AjaxReloadManager::TYPE_CONTENT, $contentElementId, ['foo-event']);

// Frontend module
$this->ajaxReloadManager->subscribe(AjaxReloadManager::TYPE_MODULE, $frontendModuleId, ['bar-event']);
```

Then, in your markup, you can refresh the subscribed modules by firing the specific event:

```html
<button>Refresh the module</button>

<script>
button.addEventListener('click', () => HasteAjaxReload.dispatchEvents('foo-event', 'bar-event'));
</script>
```

### Custom request headers

To attach your custom headers to the request, you may want to provide the event as a plain data object:

```js
HasteAjaxReload.dispatchEvents(
    {
        name: 'foo-event', 
        headers: {
            'Content-Type': 'application/json',
            'X-My-Custom-Header': 'my-custom-value',
        },
    },
);
```

### Global JavaScript event

When the helper finishes reloading the elements, it will dispatch the global event on the current document.
You can listen to it like in the below example:


```js
document.addEventListener('HasteAjaxReloadComplete', (event) => {
    console.log(event.detail);
});
```


## Notes

The utility comes with the library-agnostic JavaScript file that is added to the page when at least one listener is registered.

The frontend module and content element in its markup must have a single wrapper element which is usually the case 
with `<div>` container with `ce_` or `mod_` classes. Still, it works on other wrapping elements such as `<section>` etc.
