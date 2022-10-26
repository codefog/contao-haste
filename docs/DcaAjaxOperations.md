# DcaAjaxOperations component

This component is designed to help you handle the ajax "toggle" operations in DCA list view. 


## Usage

If you have a boolean field with a specific icon naming (e.g. `featured.svg` and `featured_.svg`), then it is best 
to use the Contao core features:

```php
'toggle' => [
    'href' => 'act=toggle&amp;field=published',
    'icon' => 'visible.svg',
    'showInHeader' => true,
],
'feature' => [
    'href' => 'act=toggle&amp;field=featured',
    'icon' => 'featured.svg',
],
```

However, suppose you have a different file naming, multiple states per field, or need a custom permission check. 
In that case, this component is for you.

Take a look at the example of how we would implement the regular "toggle published" operation:

```php
$GLOBALS['TL_DCA']['tl_table']['list']['operations']['toggle_my_field'] = [
    'attributes' => 'onclick="Backend.getScrollOffset()"',
    'haste_ajax_operation' => [
        'field' => 'my_field',
        'options' => [
            ['value' => '', 'icon' => 'invisible.svg'],
            ['value' => '1', 'icon' => 'visible.svg'],
        ],
    ],
];
```

As you can see, the `haste_ajax_operation` contains the configuration array for this operation.
We only have to tell it to what `field` we refer and the possible options with the value and the corresponding icon.


## Multiple states

If you would like to support multiple states, let's say you are a traffic light operator, you can do it as follows:

```php
'haste_ajax_operation' => [
    'field' => 'status',
    'options' => [
        ['value' => 'green', 'icon' => 'bundles/mybundle/green.svg'],
        ['value' => 'yellow', 'icon' => 'bundles/mybundle/yellow.svg'],
        ['value' => 'red', 'icon' => 'bundles/mybundle/red.svg'],
    ],
],
```

You can now click on the icon, and it will just rotate between those three options.


## Permissions check

By default, haste checks for the permissions using the security votes.
If you want to extend the permission checks, use the `check_permission_callback` modifying the `$hasPermission` by reference:

```php
'haste_ajax_operation' => [
    'field' => 'status',
    'check_permission_callback' => static function(string $table, array $settings, bool &$hasPermission) {
        $hasPermission = !check($table, $settings);
    },
],
```
