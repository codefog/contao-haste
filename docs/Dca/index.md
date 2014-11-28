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