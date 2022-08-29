# DcaDateRangeFilter component

This component generates a date range filter in your DCA backend.


## Usage

To generate a date range filter, simply add the `rangeFilter` property to your field definition. 
Also, be sure to have the `filter` enabled in your panel layout.

```php
// The "panelLayout" must contain "filter"
$GLOBALS['TL_DCA']['tl_table']['list']['sorting']['panelLayout'] = 'filter;search,limit';

$GLOBALS['TL_DCA']['tl_table']['fields']['dateField'] = [
    'exclude' => true,
    'rangeFilter' => true, // Does all the magic you need
    'inputType' => 'text',
    'eval' => ['rgxp' =>' date', 'datepicker' => true, 'tl_class' =>' w50 wizard'],
    'eval' => ['type' => 'string', 'length' => 11, 'default' => ''],
];
```
