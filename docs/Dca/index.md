# Haste Dca

Provides helper classes for Contao back end DCA's.

## Generate a back end range filter for date (date, time, datetime [datim]) fields

```php
// panelLayout must contain "filter" as usual
$GLOBALS['TL_DCA']['tl_mytable']['list']['sorting']['panelLayout'] = 'filter';

// Specify your date field
$GLOBALS['TL_DCA']['tl_mytable']['fields']['dateField']

    'label'                   => &$GLOBALS['TL_LANG']['tl_mytable']['dateField'],
    'exclude'                 => true,
    'rangeFilter'             => true, // Does all the magic you need
    'inputType'               => 'text',
    'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
    'sql'                     => "varchar(11) NOT NULL default ''"
);
```

## Easy ajax toggle operation

Contao provides a one click icon in certain places so states can be changed without
actually switching to the edit mask. You'll likely know it from the visibility
states or the "featured" feature in the news. However, it's quite tedious to
register all the callbacks needed and it's very repetitive work. That's why
Haste ships with a simple helper. Let's say you want to implement the default
"toggle visibility" feature for your "published" field:

```php
// Regular "toggle" operation but without "icon" and with the haste specific params
$GLOBALS['TL_DCA']['tl_mytable']['list']['operations']['toggle'] = [
    'label'                 => &$GLOBALS['TL_LANG']['tl_mytable']['toggle'],
    'attributes'            => 'onclick="Backend.getScrollOffset();"',
    'haste_ajax_operation'  => [
        'field'     => 'published',
        'states'    => [
            [
                'value'     => '',
                'icon'      => 'invisible.gif'
            ],
            [
                'value'     => '1',
                'icon'      => 'visible.gif'
            ]
        ]
    ]
];
```

As you can see `haste_ajax_operation` contains the configuration array for this
operation. We only have to tell it to what `field` we refer and the possible states
with the value and the corresponding icon. Done. You've just implemented the regular
toggle feature.

### Multiple states

In contrast to the default core implementation, Haste supports multiple states.
Let's say you would like to implement a light traffic system with three values:
green, orange and red. Easy:

```php
'haste_ajax_operation'  => [
    'field'     => 'state',
    'states'    => [
        [
            'value'     => 'green',
            'icon'      => 'system/modules/my_module/assets/green.png'
        ],
        [
            'value'     => 'orange',
            'icon'      => 'system/modules/my_module/assets/orange.png'
        ],
        [
            'value'     => 'red',
            'icon'      => 'system/modules/my_module/assets/red.png'
        ]
    ]
]
```

Done. You can now click on the icon and it will just rotate between those three
states.

### More customizing

If you want to dynamically define the states use the `states_callback` and make
sure the returned value has the same format as if you would have defined them
using the `states` array:

```php
'haste_ajax_operation'  => [
    'field'             => 'state',
    'states_callback'   => function() { // do whatever you like }
]
```

If you want to customize what's stored when the ajax request is executed, use
the `ajax_callback`:

```php
'haste_ajax_operation'  => [
    'field'             => 'state',
    'ajax_callback'     => function($hasteAjaxOperationSettings, $dc, $id, $currentValue) {

        // $hasteAjaxOperationSettings contains what you defined in the DCA
        // $dc contains the DataContainer
        // $id contains the ID of the row
        // $currentValue contains the current value
    }
]
```

**Important:** `$currentValue` does contain the **current** value. Not the one the user
wants in the back end. Let's say the row is currently upublished (`published` = `''`).
Now the user wants to publish it. `$currentValue` will contain `''` and YOU have
to decide what's the "next" value (in this case probably `1`).
The callback requires an array as return value in the following format:

```php
[
    'nextValue' => 'nextValue',
    'nextIcon'  => 'pathToNextIcon'
]
```

In the case of the published settings:


```php
[
    'nextValue' => '1',
    'nextIcon'  => 'system/themes/default/images/visible.gif'
]
```


